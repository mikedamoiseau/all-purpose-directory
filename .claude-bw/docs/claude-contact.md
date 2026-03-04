# Contact Form System

The Contact Form system (`src/Contact/`) allows users to send messages to listing owners.

## Classes

- `ContactForm` - Renders the contact form on listing pages
- `ContactHandler` - Processes AJAX submissions, validates, sends email

## Constants

```php
ContactForm::NONCE_ACTION    // 'apd_contact_form'
ContactForm::NONCE_NAME      // 'apd_contact_nonce'
```

## Helper Functions

```php
apd_contact_form( array $config = [] ): ContactForm           // Get ContactForm instance
apd_contact_handler( array $config = [] ): ContactHandler     // Get ContactHandler instance
apd_render_contact_form( int $listing_id, array $config = [] ): void  // Render form
apd_get_contact_form( int $listing_id, array $config = [] ): string   // Get form HTML
apd_can_receive_contact( int $listing_id ): bool              // Check if listing accepts contact
apd_process_contact( array $data, array $config = [] ): true|WP_Error // Process submission
apd_send_contact_email( int $listing_id, array $data, array $config = [] ): bool // Send email
```

## Configuration Options

```php
$config = [
    'show_phone'         => true,   // Show phone field
    'phone_required'     => false,  // Require phone
    'show_subject'       => false,  // Show subject field
    'subject_required'   => false,  // Require subject
    'min_message_length' => 10,     // Minimum message length
    'send_admin_copy'    => false,  // CC admin on emails
    'admin_email'        => '',     // Admin email (defaults to site admin)
    'class'              => '',     // Custom CSS class
];
```

## Hooks

**Actions:**
- `apd_contact_form_init` - Fired when ContactForm initializes
- `apd_contact_handler_init` - Fired when ContactHandler initializes
- `apd_contact_form_after_fields` - After form fields are rendered
- `apd_before_send_contact` - Before contact email is sent
- `apd_contact_sent` - After contact email is sent

**Filters:**
- `apd_contact_form_classes` - Modify form CSS classes
- `apd_contact_form_args` - Modify form arguments
- `apd_contact_form_html` - Filter final form HTML
- `apd_listing_can_receive_contact` - Whether listing can receive contact
- `apd_contact_validation_errors` - Modify validation errors
- `apd_contact_email_to` - Filter recipient email
- `apd_contact_email_subject` - Filter email subject
- `apd_contact_email_message` - Filter email message
- `apd_contact_email_headers` - Filter email headers
- `apd_contact_send_admin_copy` - Whether to CC admin
- `apd_contact_admin_email` - Admin email address

## Template

`templates/contact/contact-form.php` - Override in theme at `yourtheme/all-purpose-directory/contact/contact-form.php`

---

# Inquiry Tracking System

The Inquiry Tracking system (`src/Contact/InquiryTracker.php`) logs contact form submissions for history and statistics.

## Post Type

`apd_inquiry` - Private post type storing inquiry data

## Meta Keys

- `_apd_inquiry_listing_id` - Associated listing ID
- `_apd_inquiry_sender_name` - Sender's name
- `_apd_inquiry_sender_email` - Sender's email
- `_apd_inquiry_sender_phone` - Sender's phone (optional)
- `_apd_inquiry_subject` - Message subject (optional)
- `_apd_inquiry_read` - Read status (0/1)
- `_apd_inquiry_count` - Listing meta for total inquiry count

## Helper Functions

```php
apd_inquiry_tracker(): InquiryTracker                         // Get InquiryTracker instance
apd_log_inquiry( array $data, WP_Post $listing, WP_User $owner ): int|false  // Log inquiry
apd_get_inquiry( int $inquiry_id ): ?array                    // Get single inquiry
apd_get_listing_inquiries( int $listing_id, array $args ): array  // Get listing's inquiries
apd_get_user_inquiries( int $user_id, array $args ): array    // Get user's inquiries
apd_count_user_inquiries( int $user_id, string $status ): int // Count user's inquiries
apd_get_listing_inquiry_count( int $listing_id ): int         // Get listing inquiry count
apd_mark_inquiry_read( int $inquiry_id ): bool                // Mark as read
apd_mark_inquiry_unread( int $inquiry_id ): bool              // Mark as unread
apd_delete_inquiry( int $inquiry_id, bool $force ): bool      // Delete inquiry
apd_can_user_view_inquiry( int $inquiry_id, int $user_id ): bool  // Check view permission
```

## Hooks

**Actions:**
- `apd_inquiry_tracker_init` - Fired when InquiryTracker initializes
- `apd_inquiry_logged` - After inquiry is logged
- `apd_inquiry_marked_read` - After inquiry marked read
- `apd_inquiry_marked_unread` - After inquiry marked unread
- `apd_before_inquiry_delete` - Before inquiry is deleted

**Filters:**
- `apd_track_inquiry` - Whether to track this inquiry
- `apd_inquiry_post_type_args` - Modify post type args
- `apd_inquiry_post_data` - Filter inquiry post data before save
- `apd_listing_inquiries_query_args` - Modify listing inquiries query
- `apd_user_inquiries_query_args` - Modify user inquiries query
- `apd_user_can_view_inquiry` - Whether user can view inquiry

## Dashboard Integration

- Inquiry counts shown in dashboard stats (`inquiries`, `unread_inquiries`)
- Per-listing inquiry count shown in MyListings table
- Sortable by inquiry count
