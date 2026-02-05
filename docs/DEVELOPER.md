# All Purpose Directory - Developer Documentation

This guide covers extending and customizing All Purpose Directory for developers.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Action Hooks](#action-hooks)
3. [Filter Hooks](#filter-hooks)
4. [Custom Fields](#custom-fields)
5. [Custom Filters](#custom-filters)
6. [Custom Views](#custom-views)
7. [Template System](#template-system)
8. [REST API](#rest-api)
9. [Helper Functions](#helper-functions)
10. [Database Schema](#database-schema)
11. [Coding Standards](#coding-standards)

---

## Architecture Overview

### Plugin Structure

```
all-purpose-directory/
├── src/
│   ├── Core/           # Plugin bootstrap, assets, templates
│   ├── Admin/          # Admin pages, meta boxes, settings
│   ├── Listing/        # Post type, repository, queries
│   ├── Fields/         # Field registry, renderer, validator, types
│   ├── Taxonomy/       # Categories and tags
│   ├── Search/         # Search query, filters
│   ├── Frontend/       # Submission, display, dashboard
│   ├── Shortcode/      # Shortcode manager and implementations
│   ├── Blocks/         # Gutenberg blocks
│   ├── User/           # Favorites, profile
│   ├── Review/         # Reviews and ratings
│   ├── Contact/        # Contact forms, inquiry tracking
│   ├── Email/          # Email manager, templates
│   ├── Api/            # REST API controller, endpoints
│   └── Contracts/      # Interfaces
├── templates/          # Theme-overridable templates
├── assets/             # CSS, JS, images
└── includes/           # Global helper functions
```

### Key Patterns

- **Singleton Pattern**: Core classes use `get_instance()` method
- **Registry Pattern**: Fields, filters, views, shortcodes, blocks
- **Template Override**: Theme can override any template
- **Hook System**: 100+ actions and filters for extensibility

### Naming Conventions

- **Prefix**: `apd_` for functions, `APD` for classes
- **Post Type**: `apd_listing`
- **Taxonomies**: `apd_category`, `apd_tag`
- **Meta Keys**: `_apd_{field_name}`
- **Options**: `apd_options`
- **Nonces**: `apd_{action}_nonce`
- **Text Domain**: `all-purpose-directory`

---

## Action Hooks

### Core Lifecycle

```php
/**
 * Fires after the plugin initializes.
 * Use for registering custom field types, filters, views.
 */
do_action( 'apd_init' );

/**
 * Fires when all plugin components are loaded.
 * Safe to use all plugin functions.
 */
do_action( 'apd_loaded' );

/**
 * Fires after text domain is loaded.
 * Use for translation-related setup.
 */
do_action( 'apd_textdomain_loaded' );
```

### Listing Lifecycle

```php
/**
 * Before a listing is saved in admin.
 *
 * @param int   $post_id Post ID.
 * @param array $values  Raw POST values.
 */
do_action( 'apd_before_listing_save', $post_id, $values );

/**
 * After a listing is saved in admin.
 *
 * @param int   $post_id          Post ID.
 * @param array $sanitized_values Processed field values.
 */
do_action( 'apd_after_listing_save', $post_id, $sanitized_values );

/**
 * When a listing status changes.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Previous status.
 * @param WP_Post $post       Post object.
 */
do_action( 'apd_listing_status_changed', $new_status, $old_status, $post );

/**
 * When a listing is viewed (single page).
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_listing_viewed', $listing_id );
```

### Submission Lifecycle

```php
/**
 * Before frontend submission is processed.
 *
 * @param array $data Submitted form data.
 */
do_action( 'apd_before_submission', $data );

/**
 * After successful frontend submission.
 *
 * @param int   $listing_id Created listing ID.
 * @param array $data       Submitted data.
 */
do_action( 'apd_after_submission', $listing_id, $data );

/**
 * During submission validation.
 *
 * @param WP_Error $errors Validation errors object.
 * @param array    $data   Submitted data.
 */
do_action( 'apd_validate_submission', $errors, $data );

/**
 * When a spam attempt is detected.
 *
 * @param string $reason  Reason (honeypot, rate_limit, time_check).
 * @param array  $data    Submitted data.
 * @param string $ip      Client IP address.
 */
do_action( 'apd_spam_attempt_detected', $reason, $data, $ip );
```

### Field System

```php
/**
 * After a field is registered.
 *
 * @param string $name   Field name.
 * @param array  $config Field configuration.
 */
do_action( 'apd_field_registered', $name, $config );

/**
 * After a field is unregistered.
 *
 * @param string $name Field name.
 */
do_action( 'apd_field_unregistered', $name );

/**
 * After a field type handler is registered.
 *
 * @param string              $type       Field type.
 * @param FieldTypeInterface  $field_type Field type instance.
 */
do_action( 'apd_field_type_registered', $type, $field_type );

/**
 * After admin fields are rendered.
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_after_admin_fields', $listing_id );

/**
 * After frontend fields are rendered.
 *
 * @param int $listing_id Listing ID (0 for new).
 */
do_action( 'apd_after_frontend_fields', $listing_id );

/**
 * After field validation completes.
 *
 * @param WP_Error $errors  Validation errors.
 * @param array    $values  Field values.
 * @param array    $args    Validation arguments.
 * @param string   $context Validation context.
 */
do_action( 'apd_after_validate_fields', $errors, $values, $args, $context );
```

### Search & Filters

```php
/**
 * After a filter is registered.
 *
 * @param string $name   Filter name.
 * @param array  $config Filter configuration.
 */
do_action( 'apd_filter_registered', $name, $config );

/**
 * Before search form renders.
 *
 * @param array $args Form arguments.
 */
do_action( 'apd_before_search_form', $args );

/**
 * After search form renders.
 *
 * @param array $args Form arguments.
 */
do_action( 'apd_after_search_form', $args );

/**
 * After search query is modified.
 *
 * @param WP_Query $query  Query object.
 * @param array    $params Active filter parameters.
 */
do_action( 'apd_search_query_modified', $query, $params );
```

### Display & Templates

```php
/**
 * Before archive content.
 */
do_action( 'apd_before_archive' );

/**
 * After archive content.
 */
do_action( 'apd_after_archive' );

/**
 * Before single listing content.
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_before_single_listing', $listing_id );

/**
 * After single listing content.
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_after_single_listing', $listing_id );

/**
 * Single listing sections (for template customization).
 */
do_action( 'apd_single_listing_header', $listing_id );
do_action( 'apd_single_listing_content', $listing_id );
do_action( 'apd_single_listing_fields', $listing_id );
do_action( 'apd_single_listing_reviews', $listing_id );
do_action( 'apd_single_listing_contact_form', $listing_id );
do_action( 'apd_single_listing_sidebar', $listing_id );
do_action( 'apd_single_listing_related', $listing_id );

/**
 * After a view is registered.
 *
 * @param string        $name View name.
 * @param ViewInterface $view View instance.
 */
do_action( 'apd_view_registered', $name, $view );
```

### Dashboard

```php
/**
 * Dashboard lifecycle hooks.
 */
do_action( 'apd_before_dashboard' );
do_action( 'apd_after_dashboard' );
do_action( 'apd_dashboard_start' );
do_action( 'apd_dashboard_end' );
do_action( 'apd_dashboard_before_content' );
do_action( 'apd_dashboard_after_content' );

/**
 * Profile hooks.
 */
do_action( 'apd_profile_start', $user );
do_action( 'apd_profile_end', $user );
do_action( 'apd_before_save_profile', $user_id, $data );
do_action( 'apd_after_save_profile', $user_id, $data );
do_action( 'apd_profile_saved', $user_id );
do_action( 'apd_avatar_uploaded', $user_id, $attachment_id );
```

### Favorites

```php
/**
 * When a favorite is added.
 *
 * @param int $listing_id Listing ID.
 * @param int $user_id    User ID (0 for guest).
 */
do_action( 'apd_favorite_added', $listing_id, $user_id );

/**
 * When a favorite is removed.
 *
 * @param int $listing_id Listing ID.
 * @param int $user_id    User ID (0 for guest).
 */
do_action( 'apd_favorite_removed', $listing_id, $user_id );

/**
 * When all favorites are cleared.
 *
 * @param int $user_id User ID.
 */
do_action( 'apd_favorites_cleared', $user_id );
```

### Reviews

```php
/**
 * Before a review is created.
 *
 * @param array $data Review data.
 */
do_action( 'apd_before_review_create', $data );

/**
 * After a review is created.
 *
 * @param int   $review_id Review (comment) ID.
 * @param array $data      Review data.
 */
do_action( 'apd_review_created', $review_id, $data );

/**
 * Before a review is updated.
 *
 * @param int   $review_id Review ID.
 * @param array $data      Updated data.
 */
do_action( 'apd_before_review_update', $review_id, $data );

/**
 * After a review is updated.
 *
 * @param int   $review_id Review ID.
 * @param array $data      Updated data.
 */
do_action( 'apd_review_updated', $review_id, $data );

/**
 * When a review is approved.
 *
 * @param int $review_id Review ID.
 */
do_action( 'apd_review_approved', $review_id );

/**
 * When a review is rejected.
 *
 * @param int $review_id Review ID.
 */
do_action( 'apd_review_rejected', $review_id );
```

### Contact & Inquiries

```php
/**
 * Before contact form email is sent.
 *
 * @param array $data     Contact form data.
 * @param int   $listing_id Listing ID.
 */
do_action( 'apd_before_send_contact', $data, $listing_id );

/**
 * After contact form is successfully sent.
 *
 * @param array $data       Contact form data.
 * @param int   $listing_id Listing ID.
 */
do_action( 'apd_contact_sent', $data, $listing_id );

/**
 * When an inquiry is logged.
 *
 * @param int   $inquiry_id Inquiry post ID.
 * @param array $data       Inquiry data.
 */
do_action( 'apd_inquiry_logged', $inquiry_id, $data );
```

### Email

```php
/**
 * Before any email is sent.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $message Message body.
 * @param array  $headers Headers.
 */
do_action( 'apd_before_send_email', $to, $subject, $message, $headers );

/**
 * After an email is sent.
 *
 * @param bool   $sent    Whether email was sent.
 * @param string $to      Recipient.
 * @param string $subject Subject.
 */
do_action( 'apd_after_send_email', $sent, $to, $subject );
```

### REST API

```php
/**
 * After REST API controller is initialized.
 */
do_action( 'apd_rest_api_init' );

/**
 * Before routes are registered (add custom endpoints here).
 *
 * @param RestController $controller Controller instance.
 */
do_action( 'apd_register_rest_routes', $controller );

/**
 * After all routes are registered.
 */
do_action( 'apd_rest_routes_registered' );
```

### Settings

```php
/**
 * After settings are initialized.
 */
do_action( 'apd_settings_init' );

/**
 * After settings are registered (add custom sections/fields).
 */
do_action( 'apd_register_settings' );
```

---

## Filter Hooks

### Listing Data

```php
/**
 * Modify listing fields configuration.
 *
 * @param array $fields Registered fields.
 * @return array Modified fields.
 */
add_filter( 'apd_listing_fields', function( $fields ) {
    $fields['custom_field'] = [
        'type'     => 'text',
        'label'    => 'Custom Field',
        'required' => false,
    ];
    return $fields;
});

/**
 * Modify submission form fields.
 *
 * @param array $fields Fields for submission form.
 * @return array Modified fields.
 */
add_filter( 'apd_submission_fields', function( $fields ) {
    unset( $fields['admin_only_field'] );
    return $fields;
});

/**
 * Modify listing field value on retrieval.
 *
 * @param mixed  $value      Field value.
 * @param int    $listing_id Listing ID.
 * @param string $field_name Field name.
 * @return mixed Modified value.
 */
add_filter( 'apd_listing_field_value', function( $value, $listing_id, $field_name ) {
    if ( $field_name === 'price' ) {
        return (float) $value;
    }
    return $value;
}, 10, 3 );

/**
 * Modify listing card display data.
 *
 * @param array   $data    Card data.
 * @param WP_Post $listing Listing post.
 * @return array Modified data.
 */
add_filter( 'apd_listing_card_data', function( $data, $listing ) {
    $data['custom_badge'] = get_post_meta( $listing->ID, '_custom_badge', true );
    return $data;
}, 10, 2 );
```

### Query Modifications

```php
/**
 * Modify listing query arguments.
 *
 * @param array $args WP_Query arguments.
 * @return array Modified arguments.
 */
add_filter( 'apd_listing_query_args', function( $args ) {
    $args['meta_query'][] = [
        'key'     => '_featured',
        'value'   => '1',
        'compare' => '=',
    ];
    return $args;
});

/**
 * Modify search query arguments.
 *
 * @param array $args   Query arguments.
 * @param array $params Active filter parameters.
 * @return array Modified arguments.
 */
add_filter( 'apd_search_query_args', function( $args, $params ) {
    // Custom query modifications
    return $args;
}, 10, 2 );
```

### Submission

```php
/**
 * Modify submitted form data before processing.
 *
 * @param array $data Form data.
 * @return array Modified data.
 */
add_filter( 'apd_submission_form_data', function( $data ) {
    $data['custom_value'] = 'default';
    return $data;
});

/**
 * Modify new listing post data.
 *
 * @param array $post_data Post data for wp_insert_post.
 * @param array $form_data Submitted form data.
 * @return array Modified post data.
 */
add_filter( 'apd_new_listing_post_data', function( $post_data, $form_data ) {
    $post_data['post_status'] = 'pending';
    return $post_data;
}, 10, 2 );

/**
 * Filter default status for new submissions.
 *
 * @param string $status Default status.
 * @return string Modified status.
 */
add_filter( 'apd_submission_default_status', function( $status ) {
    return current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
});

/**
 * Filter whether user can submit listings.
 *
 * @param bool $can_submit Whether user can submit.
 * @param int  $user_id    User ID.
 * @return bool Modified value.
 */
add_filter( 'apd_user_can_submit_listing', function( $can_submit, $user_id ) {
    // Check subscription status, etc.
    return $can_submit && user_has_active_subscription( $user_id );
}, 10, 2 );

/**
 * Filter whether user can edit a listing.
 *
 * @param bool $can_edit   Whether user can edit.
 * @param int  $listing_id Listing ID.
 * @param int  $user_id    User ID.
 * @return bool Modified value.
 */
add_filter( 'apd_user_can_edit_listing', function( $can_edit, $listing_id, $user_id ) {
    return $can_edit;
}, 10, 3 );
```

### Spam Protection

```php
/**
 * Bypass spam protection for trusted users.
 *
 * @param bool $bypass Whether to bypass.
 * @return bool Modified value.
 */
add_filter( 'apd_bypass_spam_protection', function( $bypass ) {
    return current_user_can( 'edit_others_posts' );
});

/**
 * Custom spam check (e.g., reCAPTCHA).
 *
 * @param bool  $is_spam Whether submission is spam.
 * @param array $data    Submitted data.
 * @return bool Modified value.
 */
add_filter( 'apd_submission_spam_check', function( $is_spam, $data ) {
    // Integrate reCAPTCHA or other service
    return $is_spam || ! verify_recaptcha( $data['g-recaptcha-response'] );
}, 10, 2 );

/**
 * Modify rate limit settings.
 */
add_filter( 'apd_submission_rate_limit', function( $limit ) {
    return 10; // Allow 10 submissions per period
});

add_filter( 'apd_submission_rate_period', function( $seconds ) {
    return 3600; // Per hour
});
```

### Field System

```php
/**
 * Modify field configuration before registration.
 *
 * @param array  $config Field config.
 * @param string $name   Field name.
 * @return array Modified config.
 */
add_filter( 'apd_register_field_config', function( $config, $name ) {
    if ( $name === 'price' ) {
        $config['required'] = true;
    }
    return $config;
}, 10, 2 );

/**
 * Modify field when retrieved.
 *
 * @param array  $field Field config.
 * @param string $name  Field name.
 * @return array Modified field.
 */
add_filter( 'apd_get_field', function( $field, $name ) {
    return $field;
}, 10, 2 );

/**
 * Modify field validation result.
 *
 * @param bool|WP_Error $result Validation result.
 * @param string        $name   Field name.
 * @param mixed         $value  Field value.
 * @param array         $field  Field config.
 * @return bool|WP_Error Modified result.
 */
add_filter( 'apd_validate_field', function( $result, $name, $value, $field ) {
    return $result;
}, 10, 4 );

/**
 * Modify rendered field HTML.
 *
 * @param string $html       Rendered HTML.
 * @param array  $field      Field config.
 * @param mixed  $value      Field value.
 * @param string $context    Render context.
 * @param int    $listing_id Listing ID.
 * @return string Modified HTML.
 */
add_filter( 'apd_render_field', function( $html, $field, $value, $context, $listing_id ) {
    return $html;
}, 10, 5 );

/**
 * Control whether a field should be displayed.
 *
 * @param bool   $display    Whether to display.
 * @param array  $field      Field config.
 * @param string $context    Render context.
 * @param int    $listing_id Listing ID.
 * @return bool Modified value.
 */
add_filter( 'apd_should_display_field', function( $display, $field, $context, $listing_id ) {
    if ( $field['name'] === 'premium_field' && ! is_premium_listing( $listing_id ) ) {
        return false;
    }
    return $display;
}, 10, 4 );
```

### Search Filters

```php
/**
 * Modify search filters configuration.
 *
 * @param array $filters Registered filters.
 * @return array Modified filters.
 */
add_filter( 'apd_search_filters', function( $filters ) {
    $filters['custom_filter'] = [
        'type'  => 'select',
        'label' => 'Custom Filter',
        'options' => [ 'a' => 'Option A', 'b' => 'Option B' ],
    ];
    return $filters;
});

/**
 * Modify filter options dynamically.
 *
 * @param array  $options Filter options.
 * @param string $name    Filter name.
 * @return array Modified options.
 */
add_filter( 'apd_filter_options', function( $options, $name ) {
    return $options;
}, 10, 2 );
```

### Templates

```php
/**
 * Modify template path lookup.
 *
 * @param string $located  Located template path.
 * @param string $template Template name.
 * @param array  $args     Template arguments.
 * @return string Modified path.
 */
add_filter( 'apd_locate_template', function( $located, $template, $args ) {
    return $located;
}, 10, 3 );
```

### Shortcodes

```php
/**
 * Modify shortcode attributes.
 *
 * @param array $atts Shortcode attributes.
 * @return array Modified attributes.
 */
add_filter( 'apd_shortcode_apd_listings_atts', function( $atts ) {
    $atts['columns'] = 4;
    return $atts;
});

/**
 * Modify shortcode output.
 *
 * @param string $output Shortcode HTML output.
 * @param array  $atts   Shortcode attributes.
 * @return string Modified output.
 */
add_filter( 'apd_shortcode_apd_listings_output', function( $output, $atts ) {
    return '<div class="custom-wrapper">' . $output . '</div>';
}, 10, 2 );
```

### Dashboard

```php
/**
 * Modify dashboard tabs.
 *
 * @param array $tabs Dashboard tabs.
 * @return array Modified tabs.
 */
add_filter( 'apd_dashboard_tabs', function( $tabs ) {
    $tabs['custom'] = [
        'label' => 'Custom Tab',
        'callback' => 'render_custom_tab',
    ];
    return $tabs;
});

/**
 * Modify dashboard stats.
 *
 * @param array $stats Stats array.
 * @param int   $user_id User ID.
 * @return array Modified stats.
 */
add_filter( 'apd_dashboard_stats', function( $stats, $user_id ) {
    $stats['custom_count'] = get_custom_count( $user_id );
    return $stats;
}, 10, 2 );
```

### Favorites

```php
/**
 * Require login for favorites.
 *
 * @param bool $require Whether to require login.
 * @return bool Modified value.
 */
add_filter( 'apd_favorites_require_login', '__return_true' );

/**
 * Enable guest favorites.
 *
 * @param bool $enabled Whether guest favorites are enabled.
 * @return bool Modified value.
 */
add_filter( 'apd_guest_favorites_enabled', '__return_true' );
```

### Reviews

```php
/**
 * Require login for reviews.
 *
 * @param bool $require Whether to require login.
 * @return bool Modified value.
 */
add_filter( 'apd_reviews_require_login', '__return_true' );

/**
 * Set minimum review content length.
 *
 * @param int $length Minimum characters.
 * @return int Modified length.
 */
add_filter( 'apd_review_min_content_length', function( $length ) {
    return 50;
});

/**
 * Modify review data before saving.
 *
 * @param array $data Review data.
 * @return array Modified data.
 */
add_filter( 'apd_review_data', function( $data ) {
    return $data;
});

/**
 * Set default review status.
 *
 * @param string $status Default status (0=pending, 1=approved).
 * @return string Modified status.
 */
add_filter( 'apd_review_default_status', function( $status ) {
    return current_user_can( 'moderate_comments' ) ? '1' : '0';
});
```

### Contact Form

```php
/**
 * Control which listings can receive contact.
 *
 * @param bool $can_receive Whether listing can receive contact.
 * @param int  $listing_id  Listing ID.
 * @return bool Modified value.
 */
add_filter( 'apd_listing_can_receive_contact', function( $can_receive, $listing_id ) {
    return $can_receive;
}, 10, 2 );

/**
 * Modify contact form validation errors.
 *
 * @param WP_Error $errors    Validation errors.
 * @param array    $data      Form data.
 * @param int      $listing_id Listing ID.
 * @return WP_Error Modified errors.
 */
add_filter( 'apd_contact_validation_errors', function( $errors, $data, $listing_id ) {
    return $errors;
}, 10, 3 );

/**
 * Modify contact email recipient.
 *
 * @param string $to         Recipient email.
 * @param int    $listing_id Listing ID.
 * @param array  $data       Form data.
 * @return string Modified email.
 */
add_filter( 'apd_contact_email_to', function( $to, $listing_id, $data ) {
    return $to;
}, 10, 3 );

/**
 * Modify contact email subject.
 *
 * @param string $subject    Email subject.
 * @param int    $listing_id Listing ID.
 * @param array  $data       Form data.
 * @return string Modified subject.
 */
add_filter( 'apd_contact_email_subject', function( $subject, $listing_id, $data ) {
    return $subject;
}, 10, 3 );
```

### Email

```php
/**
 * Modify email recipient.
 *
 * @param string $to   Recipient.
 * @param string $type Email type.
 * @param array  $context Email context.
 * @return string Modified recipient.
 */
add_filter( 'apd_email_to', function( $to, $type, $context ) {
    return $to;
}, 10, 3 );

/**
 * Modify email subject.
 *
 * @param string $subject Email subject.
 * @param string $type    Email type.
 * @param array  $context Email context.
 * @return string Modified subject.
 */
add_filter( 'apd_email_subject', function( $subject, $type, $context ) {
    return $subject;
}, 10, 3 );

/**
 * Control whether a notification is enabled.
 *
 * @param bool   $enabled Whether enabled.
 * @param string $type    Notification type.
 * @return bool Modified value.
 */
add_filter( 'apd_email_notification_enabled', function( $enabled, $type ) {
    return $enabled;
}, 10, 2 );

/**
 * Customize email colors.
 */
add_filter( 'apd_email_header_color', function( $color ) {
    return '#1a73e8';
});

add_filter( 'apd_email_button_color', function( $color ) {
    return '#1a73e8';
});

/**
 * Add custom placeholders to emails.
 *
 * @param string $message Email message.
 * @param array  $context Email context.
 * @return string Modified message.
 */
add_filter( 'apd_email_replace_placeholders', function( $message, $context ) {
    $message = str_replace( '{custom_value}', 'replacement', $message );
    return $message;
}, 10, 2 );
```

### Settings

```php
/**
 * Modify settings tabs.
 *
 * @param array $tabs Settings tabs.
 * @return array Modified tabs.
 */
add_filter( 'apd_settings_tabs', function( $tabs ) {
    $tabs['custom'] = 'Custom Settings';
    return $tabs;
});

/**
 * Modify default settings.
 *
 * @param array $defaults Default settings.
 * @return array Modified defaults.
 */
add_filter( 'apd_settings_defaults', function( $defaults ) {
    $defaults['custom_option'] = 'default_value';
    return $defaults;
});

/**
 * Modify settings before saving.
 *
 * @param array $settings Sanitized settings.
 * @return array Modified settings.
 */
add_filter( 'apd_sanitize_settings', function( $settings ) {
    return $settings;
});
```

---

## Custom Fields

### Registering Fields

```php
add_filter( 'apd_listing_fields', function( $fields ) {
    // Simple text field
    $fields['business_hours'] = [
        'type'        => 'textarea',
        'label'       => 'Business Hours',
        'description' => 'Enter your operating hours',
        'required'    => false,
        'rows'        => 4,
    ];

    // Select field with options
    $fields['property_type'] = [
        'type'        => 'select',
        'label'       => 'Property Type',
        'required'    => true,
        'options'     => [
            'house'     => 'House',
            'apartment' => 'Apartment',
            'condo'     => 'Condo',
            'land'      => 'Land',
        ],
        'filterable'  => true,
    ];

    // Price field with currency
    $fields['price'] = [
        'type'            => 'currency',
        'label'           => 'Price',
        'required'        => true,
        'min'             => 0,
        'currency_symbol' => '$',
        'filterable'      => true,
        'sortable'        => true,
    ];

    // Gallery field
    $fields['gallery'] = [
        'type'       => 'gallery',
        'label'      => 'Photo Gallery',
        'max_images' => 10,
    ];

    return $fields;
});
```

### Field Configuration Options

```php
[
    'name'        => 'field_name',      // Auto-generated from key
    'type'        => 'text',            // Field type
    'label'       => 'Field Label',
    'description' => 'Help text',
    'required'    => false,
    'default'     => '',
    'placeholder' => '',
    'options'     => [],                // For select/radio/checkbox
    'validation'  => [                  // Custom validation
        'min_length' => 10,
        'max_length' => 500,
        'pattern'    => '/^[A-Z]/',     // Regex
        'callback'   => 'my_validator', // Custom function
    ],
    'searchable'  => false,             // Include in search
    'filterable'  => false,             // Show in filters
    'sortable'    => false,             // Allow sorting
    'admin_only'  => false,             // Hide from frontend
    'priority'    => 10,                // Display order
    'class'       => '',                // CSS classes
    'attributes'  => [],                // HTML attributes
]
```

### Creating Custom Field Types

```php
use APD\Contracts\FieldTypeInterface;
use APD\Fields\AbstractFieldType;

class CustomFieldType extends AbstractFieldType {
    public function getType(): string {
        return 'custom';
    }

    public function render( array $field, mixed $value ): string {
        return sprintf(
            '<input type="text" name="%s" value="%s" class="custom-input" />',
            esc_attr( $field['name'] ),
            esc_attr( $value )
        );
    }

    public function sanitize( mixed $value ): mixed {
        return sanitize_text_field( $value );
    }

    public function validate( mixed $value, array $field ): bool|WP_Error {
        if ( $field['required'] && empty( $value ) ) {
            return new WP_Error( 'required', 'This field is required.' );
        }
        return true;
    }

    public function supports( string $feature ): bool {
        return in_array( $feature, [ 'searchable', 'sortable' ], true );
    }

    public function formatValue( mixed $value, array $field ): string {
        return esc_html( $value );
    }
}

// Register the field type
add_action( 'apd_init', function() {
    apd_register_field_type( new CustomFieldType() );
});
```

---

## Custom Filters

### Registering Filters

```php
add_filter( 'apd_search_filters', function( $filters ) {
    $filters['price_range'] = [
        'type'           => 'range',
        'label'          => 'Price Range',
        'field'          => 'price',
        'min'            => 0,
        'max'            => 1000000,
        'step'           => 1000,
        'query_callback' => function( $query_args, $value ) {
            if ( ! empty( $value['min'] ) ) {
                $query_args['meta_query'][] = [
                    'key'     => '_apd_price',
                    'value'   => $value['min'],
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ];
            }
            if ( ! empty( $value['max'] ) ) {
                $query_args['meta_query'][] = [
                    'key'     => '_apd_price',
                    'value'   => $value['max'],
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ];
            }
            return $query_args;
        },
    ];

    return $filters;
});
```

### Filter Types

- `keyword` - Text search input
- `select` - Dropdown selection
- `checkbox` - Multiple checkboxes
- `range` - Min/max numeric inputs
- `date_range` - Start/end date inputs

---

## Custom Views

### Creating a Custom View

```php
use APD\Contracts\ViewInterface;
use APD\Frontend\Display\AbstractView;

class MapView extends AbstractView {
    public function get_name(): string {
        return 'map';
    }

    public function get_label(): string {
        return __( 'Map View', 'my-plugin' );
    }

    public function get_icon(): string {
        return 'dashicons-location';
    }

    public function render( array $listings, array $args = [] ): string {
        ob_start();
        // Render map with listings
        echo '<div class="apd-map-view" data-listings="' . esc_attr( json_encode( $this->get_map_data( $listings ) ) ) . '"></div>';
        return ob_get_clean();
    }

    public function supports( string $feature ): bool {
        return in_array( $feature, [ 'ajax' ], true );
    }
}

// Register the view
add_action( 'apd_views_init', function() {
    apd_register_view( new MapView() );
});
```

---

## Template System

### Template Hierarchy

1. Child theme: `wp-content/themes/child-theme/all-purpose-directory/`
2. Parent theme: `wp-content/themes/parent-theme/all-purpose-directory/`
3. Plugin: `wp-content/plugins/all-purpose-directory/templates/`

### Using Templates

```php
// Get template with data
apd_get_template( 'listing-card.php', [
    'listing' => $post,
    'view'    => 'grid',
] );

// Get template as string
$html = apd_get_template_html( 'listing-card.php', [ 'listing' => $post ] );

// Check if template exists
if ( apd_template_exists( 'custom-template.php' ) ) {
    apd_get_template( 'custom-template.php' );
}

// Check if theme overrides template
if ( apd_is_template_overridden( 'single-listing.php' ) ) {
    // Theme has custom template
}
```

### Template Data

Templates receive data as extracted variables:

```php
// In your code
apd_get_template( 'listing-card.php', [
    'listing'     => $post,
    'show_image'  => true,
    'custom_data' => 'value',
] );

// In listing-card.php
echo $listing->post_title;
if ( $show_image ) {
    echo get_the_post_thumbnail( $listing->ID );
}
echo $custom_data;
```

---

## REST API

### Namespace

All endpoints use namespace `apd/v1`:
- Base URL: `/wp-json/apd/v1/`

### Authentication

Authenticated requests require:
- Logged-in user with valid nonce
- `X-WP-Nonce` header with `wp_rest` nonce

### Endpoints

| Endpoint | Methods | Permission |
|----------|---------|------------|
| `/listings` | GET, POST | Public / Auth |
| `/listings/{id}` | GET, PUT, DELETE | Public / Owner |
| `/categories` | GET | Public |
| `/categories/{id}` | GET | Public |
| `/tags` | GET | Public |
| `/tags/{id}` | GET | Public |
| `/favorites` | GET, POST | Authenticated |
| `/favorites/{id}` | DELETE | Authenticated |
| `/favorites/toggle/{id}` | POST | Authenticated |
| `/reviews` | GET, POST | Public / Auth |
| `/reviews/{id}` | GET, PUT, DELETE | Public / Author |
| `/listings/{id}/reviews` | GET | Public |
| `/inquiries` | GET | Authenticated |
| `/inquiries/{id}` | GET, DELETE | Owner |
| `/inquiries/{id}/read` | POST | Owner |
| `/listings/{id}/inquiries` | GET | Owner |

### Adding Custom Endpoints

```php
add_action( 'apd_register_rest_routes', function( $controller ) {
    register_rest_route( 'apd/v1', '/custom', [
        'methods'             => 'GET',
        'callback'            => 'my_custom_endpoint',
        'permission_callback' => [ $controller, 'permission_authenticated' ],
    ] );
});

function my_custom_endpoint( WP_REST_Request $request ) {
    return apd_rest_response( [ 'data' => 'value' ] );
}
```

### Response Helpers

```php
// Success response
return apd_rest_response( $data, 200, $headers );

// Error response
return apd_rest_error( 'error_code', 'Error message', 400 );

// Paginated response
return apd_rest_paginated_response( $items, $total, $page, $per_page );
```

---

## Helper Functions

### Listings

```php
apd_get_listing_field( $listing_id, $field_name, $default );
apd_set_listing_field( $listing_id, $field_name, $value );
apd_get_listing_views( $listing_id );
apd_increment_listing_views( $listing_id );
apd_get_related_listings( $listing_id, $limit, $args );
```

### Taxonomies

```php
apd_get_listing_categories( $listing_id );
apd_get_listing_tags( $listing_id );
apd_get_category_listings( $category_id, $args );
apd_get_categories_with_count( $args );
apd_get_category_icon( $category );
apd_get_category_color( $category );
```

### Favorites

```php
apd_add_favorite( $listing_id, $user_id );
apd_remove_favorite( $listing_id, $user_id );
apd_toggle_favorite( $listing_id, $user_id );
apd_is_favorite( $listing_id, $user_id );
apd_get_user_favorites( $user_id );
apd_get_favorites_count( $user_id );
apd_get_listing_favorites_count( $listing_id );
```

### Reviews

```php
apd_get_listing_reviews( $listing_id, $args );
apd_get_listing_rating( $listing_id );
apd_get_listing_review_count( $listing_id );
apd_create_review( $listing_id, $data );
apd_update_review( $review_id, $data );
apd_delete_review( $review_id );
apd_current_user_has_reviewed( $listing_id );
```

### Settings

```php
apd_get_setting( $key, $default );
apd_set_setting( $key, $value );
apd_get_all_settings();
apd_reviews_enabled();
apd_favorites_enabled();
apd_contact_form_enabled();
apd_format_price( $amount );
```

### Templates

```php
apd_get_template( $template, $args );
apd_get_template_part( $slug, $name, $args );
apd_get_template_html( $template, $args );
apd_template_exists( $template );
apd_locate_template( $template );
```

### Caching

```php
apd_cache_remember( $key, $callback, $expiration );
apd_cache_get( $key );
apd_cache_set( $key, $value, $expiration );
apd_cache_delete( $key );
apd_cache_clear_all();
```

---

## Database Schema

The plugin uses WordPress native tables:

### Posts Table (`wp_posts`)

- Post type: `apd_listing`
- Post type: `apd_inquiry` (for tracked inquiries)

### Post Meta Table (`wp_postmeta`)

All custom fields use prefix `_apd_`:

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_views_count` | int | View count |
| `_apd_average_rating` | float | Cached average rating |
| `_apd_favorite_count` | int | Times favorited |
| `_apd_{field_name}` | mixed | Custom field values |

### Terms Tables (`wp_terms`, `wp_term_taxonomy`)

- Taxonomy: `apd_category`
- Taxonomy: `apd_tag`

### Term Meta Table (`wp_termmeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_category_icon` | string | Dashicon class |
| `_apd_category_color` | string | Hex color |

### Comments Table (`wp_comments`)

- Comment type: `apd_review`

### Comment Meta Table (`wp_commentmeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_rating` | int | Star rating (1-5) |
| `_apd_review_title` | string | Review title |

### User Meta Table (`wp_usermeta`)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_apd_favorites` | array | Favorited listing IDs |
| `_apd_phone` | string | Phone number |
| `_apd_avatar` | int | Custom avatar attachment ID |
| `_apd_social_*` | string | Social media URLs |

### Options Table (`wp_options`)

| Option Name | Type | Description |
|-------------|------|-------------|
| `apd_options` | array | Plugin settings |
| `apd_version` | string | Installed version |

---

## Coding Standards

### PHP Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use strict types: `declare(strict_types=1);`
- PHP 8.0+ features allowed
- All functions and classes must have PHPDoc

### JavaScript Standards

- Follow [WordPress JavaScript Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- ES6+ features allowed
- Use `wp.i18n` for translations

### CSS Standards

- Follow [WordPress CSS Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- Use BEM naming: `.apd-block__element--modifier`
- Prefix all classes with `apd-`

### Security

- Always escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Always sanitize input: `sanitize_text_field()`, `absint()`
- Always verify nonces: `wp_verify_nonce()`
- Always check capabilities: `current_user_can()`
- Always use prepared statements: `$wpdb->prepare()`

### Testing

- Unit tests in `tests/unit/`
- Integration tests in `tests/integration/`
- E2E tests in `tests/e2e/`
- Run tests: `composer test:unit`
