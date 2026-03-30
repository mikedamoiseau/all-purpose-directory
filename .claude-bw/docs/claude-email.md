# Email System API Reference

The Email system provides centralized email management with HTML templates, placeholder replacement, and notification handling.

## EmailManager Class

Located at `src/Email/EmailManager.php`.

### Singleton Access

```php
$manager = \APD\Email\EmailManager::get_instance();
// or via helper function:
$manager = apd_email_manager();
```

### Configuration

Default configuration values:

| Key | Default | Description |
|-----|---------|-------------|
| `from_name` | Site name | Sender name |
| `from_email` | Admin email | Sender email |
| `admin_email` | Admin email | Notification recipient |
| `content_type` | `text/html` | Email content type |
| `charset` | `UTF-8` | Character encoding |
| `enable_html` | `true` | Enable HTML emails |
| `use_templates` | `true` | Use template files |

#### Setting Configuration

```php
$manager->set_config([
    'from_name' => 'My Directory',
    'from_email' => 'noreply@example.com',
    'admin_email' => 'admin@example.com',
]);
```

### Notification Types

All notification types are enabled by default.

| Type | Description | Recipient |
|------|-------------|-----------|
| `listing_submitted` | New listing submitted | Admin |
| `listing_approved` | Listing approved | Author |
| `listing_rejected` | Listing not approved | Author |
| `listing_expiring` | Listing expires soon | Author |
| `listing_expired` | Listing has expired | Author |
| `new_review` | New review received | Listing author |
| `new_inquiry` | New contact inquiry | Listing author |

#### Enable/Disable Notifications

```php
// Check if enabled
$enabled = apd_is_email_notification_enabled('listing_submitted');

// Disable a notification
apd_set_email_notification_enabled('listing_submitted', false);

// Or via manager
$manager->set_notification_enabled('new_review', false);
```

## Helper Functions

### Sending Emails

```php
// Generic email
apd_send_email(
    'user@example.com',
    'Subject with {site_name}',
    'Hello {user_name}!',
    [],           // headers
    ['user_name' => 'John']  // context
);

// Notification emails
apd_send_listing_submitted_email($listing_id);
apd_send_listing_approved_email($listing_id);
apd_send_listing_rejected_email($listing_id, 'Optional reason');
apd_send_listing_expiring_email($listing_id, 7); // days left
apd_send_listing_expired_email($listing_id);
apd_send_new_review_email($review_id);
apd_send_new_inquiry_email($listing_id, [
    'name' => 'John',
    'email' => 'john@example.com',
    'phone' => '123-456-7890',
    'message' => 'Inquiry message',
]);
```

### Placeholders

Default placeholders available in all emails:

| Placeholder | Description |
|-------------|-------------|
| `{site_name}` | Site name |
| `{site_url}` | Home URL |
| `{admin_email}` | Admin email |
| `{current_date}` | Current date |
| `{current_time}` | Current time |

#### Listing Context Placeholders

When using `get_listing_context()`:

| Placeholder | Description |
|-------------|-------------|
| `{listing_id}` | Listing ID |
| `{listing_title}` | Listing title |
| `{listing_url}` | Listing permalink |
| `{listing_edit_url}` | Admin edit URL |
| `{listing_status}` | Post status |
| `{author_name}` | Author display name |
| `{author_email}` | Author email |
| `{author_id}` | Author user ID |
| `{admin_url}` | Admin listings URL |

#### User Context Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{user_id}` | User ID |
| `{user_name}` | Display name |
| `{user_email}` | Email address |
| `{user_login}` | Username |
| `{user_first_name}` | First name |
| `{user_last_name}` | Last name |

#### Review Context Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{review_id}` | Review comment ID |
| `{review_author}` | Reviewer name |
| `{review_email}` | Reviewer email |
| `{review_content}` | Review text |
| `{review_rating}` | Rating (1-5) |
| `{review_title}` | Review title |
| `{review_date}` | Review date |

#### Custom Placeholders

```php
// Register a custom placeholder
apd_register_email_placeholder('custom_value', function($context) {
    return 'My custom value';
});

// Replace placeholders in text
$text = apd_replace_email_placeholders('Hello {custom_value}!');
```

### Context Helpers

```php
// Get listing context for emails
$context = apd_get_email_listing_context($listing_id);

// Get user context
$context = apd_get_email_user_context($user_id);

// Get review context
$context = apd_get_email_review_context($review_id);
```

### Settings

```php
// Get admin email
$email = apd_get_notification_admin_email();

// Get from settings
$name = apd_get_email_from_name();
$email = apd_get_email_from_email();
```

## Email Templates

Templates are located in `templates/emails/` and can be overridden in themes at `damdir-directory/emails/`.

### Available Templates

| Template | Description |
|----------|-------------|
| `email-wrapper.php` | HTML wrapper for all emails |
| `listing-submitted.php` | New listing notification |
| `listing-approved.php` | Listing approved notification |
| `listing-rejected.php` | Listing rejected notification |
| `listing-expiring.php` | Listing expiring soon |
| `listing-expired.php` | Listing expired notification |
| `new-review.php` | New review notification |
| `new-inquiry.php` | New inquiry notification |

### Template Variables

Each template receives context variables. For example, `listing-approved.php`:

```php
/**
 * @var int    $listing_id
 * @var string $listing_title
 * @var string $listing_url
 * @var string $author_name
 */
```

### Customizing the Wrapper

The `email-wrapper.php` template wraps all email content. Override it to customize:
- Header/footer design
- Colors and branding
- Typography

Color filters available:

```php
add_filter('apd_email_header_color', fn() => '#4CAF50');
add_filter('apd_email_header_text_color', fn() => '#ffffff');
add_filter('apd_email_button_color', fn() => '#4CAF50');
```

## Hooks

### Actions

| Hook | Description | Parameters |
|------|-------------|------------|
| `apd_email_manager_init` | After manager initializes | - |
| `apd_before_send_email` | Before sending email | `$to, $subject, $message, $headers, $context` |
| `apd_after_send_email` | After sending email | `$sent, $to, $subject, $context` |

### Filters

| Filter | Description | Parameters |
|--------|-------------|------------|
| `apd_email_to` | Filter recipient | `$to, $subject, $context` |
| `apd_email_subject` | Filter subject | `$subject, $to, $context` |
| `apd_email_message` | Filter message | `$message, $to, $subject, $context` |
| `apd_email_headers` | Filter headers | `$headers, $to, $subject, $context` |
| `apd_email_from_name` | Filter from name | `$name` |
| `apd_email_from_email` | Filter from email | `$email` |
| `apd_email_admin_email` | Filter admin email | `$email` |
| `apd_email_notification_enabled` | Filter if notification enabled | `$enabled, $type` |
| `apd_email_replace_placeholders` | Filter after placeholder replacement | `$text, $context` |
| `apd_email_plain_text_message` | Filter plain text fallback | `$message, $template, $context` |
| `apd_email_header_color` | Filter header background | `$color` |
| `apd_email_header_text_color` | Filter header text color | `$color` |
| `apd_email_button_color` | Filter button color | `$color` |

## Auto-Triggered Emails

The EmailManager automatically hooks into existing actions:

| Action | Triggers |
|--------|----------|
| `apd_after_submission` | `listing_submitted` email |
| `apd_listing_status_changed` | `listing_approved`, `listing_rejected`, `listing_expired` |
| `apd_review_created` | `new_review` email (3 args: `$review_id`, `$listing_id`, `$data`) |
| `apd_inquiry_logged` | (inquiry email already sent by ContactHandler) |

## Examples

### Custom Notification

```php
// Create custom email using the system
add_action('my_custom_event', function($listing_id) {
    $context = apd_get_email_listing_context($listing_id);

    apd_send_email(
        $context['author_email'],
        '[{site_name}] Custom notification: {listing_title}',
        'Your listing {listing_title} has a custom event!',
        [],
        $context
    );
});
```

### Disable Specific Notification

```php
add_action('init', function() {
    apd_set_email_notification_enabled('listing_submitted', false);
});
```

### Add Custom Data to Emails

```php
add_filter('apd_email_message', function($message, $to, $subject, $context) {
    // Add tracking pixel or custom content
    $message .= '<img src="https://example.com/track.gif" />';
    return $message;
}, 10, 4);
```

### Custom Template

Create `themes/your-theme/damdir-directory/emails/listing-approved.php`:

```php
<?php
/**
 * Custom listing approved email.
 */
?>
<h2>Congratulations, <?php echo esc_html($author_name); ?>!</h2>
<p>Your listing "<?php echo esc_html($listing_title); ?>" is now live!</p>
<p><a href="<?php echo esc_url($listing_url); ?>" class="button">View Listing</a></p>
```
