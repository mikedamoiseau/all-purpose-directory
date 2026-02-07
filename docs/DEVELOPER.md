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
10. [WP-CLI Commands](#wp-cli-commands)
11. [Database Schema](#database-schema)
12. [Coding Standards](#coding-standards)

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
│   ├── CLI/            # WP-CLI commands
│   └── Contracts/      # Interfaces
├── templates/          # Theme-overridable templates
├── assets/             # CSS, JS, images
└── includes/           # Global helper functions
```

### Key Patterns

- **Singleton Pattern**: Core classes use `get_instance()` method
- **Registry Pattern**: Fields, filters, views, shortcodes, blocks
- **Template Override**: Theme can override any template
- **Hook System**: 150+ actions and 180+ filters for extensibility

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
 * Fires after the plugin is activated.
 */
do_action( 'apd_activated' );

/**
 * Fires after the plugin is deactivated.
 */
do_action( 'apd_deactivated' );

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
 * @param int    $post_id    Post ID.
 * @param string $new_status New status.
 * @param string $old_status Previous status.
 */
do_action( 'apd_listing_status_changed', $post_id, $new_status, $old_status );

/**
 * When a listing is viewed (single page).
 *
 * @param int $listing_id Listing ID.
 * @param int $views      Updated view count.
 */
do_action( 'apd_listing_viewed', $listing_id, $views );
```

### Submission Lifecycle

```php
/**
 * Before frontend submission is processed.
 *
 * @param array $data       Submitted form data.
 * @param int   $listing_id Listing ID (0 for new).
 */
do_action( 'apd_before_submission', $data, $listing_id );

/**
 * After successful frontend submission.
 *
 * @param int   $listing_id Created listing ID.
 * @param array $data       Submitted data.
 * @param bool  $is_new     Whether this is a new listing (not an update).
 */
do_action( 'apd_after_submission', $listing_id, $data, $is_new );

/**
 * During submission validation.
 *
 * @param WP_Error $errors Validation errors object.
 * @param array    $data   Submitted data.
 */
do_action( 'apd_validate_submission', $errors, $data );

/**
 * Before a listing update via frontend submission.
 *
 * @param int   $listing_id Listing ID.
 * @param array $post_data  Post data array.
 */
do_action( 'apd_before_listing_update', $listing_id, $post_data );

/**
 * After a listing is saved (created or updated) via frontend submission.
 *
 * @param int   $listing_id Listing ID.
 * @param array $data       Submitted data.
 * @param bool  $is_update  Whether this was an update.
 */
do_action( 'apd_listing_saved', $listing_id, $data, $is_update );

/**
 * After listing field values are saved via frontend submission.
 *
 * @param int   $listing_id Listing ID.
 * @param array $values     Saved field values.
 */
do_action( 'apd_listing_fields_saved', $listing_id, $values );

/**
 * After taxonomies are assigned to a listing via frontend submission.
 *
 * @param int   $listing_id Listing ID.
 * @param array $categories Category IDs.
 * @param array $tags       Tag names.
 */
do_action( 'apd_listing_taxonomies_assigned', $listing_id, $categories, $tags );

/**
 * When a spam attempt is detected.
 *
 * @param string $type    Spam type (honeypot, rate_limit, time_check).
 * @param string $ip      Client IP address.
 * @param int    $user_id User ID (0 for guest).
 * @param array  $data    Submitted data (optional, only from submission handler).
 */
do_action( 'apd_spam_attempt_detected', $type, $ip, $user_id, $data );
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
 * @param string $name   Field name.
 * @param array  $config Field configuration.
 */
do_action( 'apd_field_unregistered', $name, $config );

/**
 * After a field type handler is registered.
 *
 * @param string             $type       Field type.
 * @param FieldTypeInterface $field_type Field type instance.
 */
do_action( 'apd_field_type_registered', $type, $field_type );

/**
 * After admin fields are rendered.
 *
 * @param int   $listing_id Listing ID.
 * @param array $values     Field values.
 */
do_action( 'apd_after_admin_fields', $listing_id, $values );

/**
 * After frontend fields are rendered.
 *
 * @param int   $listing_id Listing ID (0 for new).
 * @param array $values     Field values.
 */
do_action( 'apd_after_frontend_fields', $listing_id, $values );

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
 * After a filter is unregistered.
 *
 * @param string $name   Filter name.
 * @param array  $config Filter configuration.
 */
do_action( 'apd_filter_unregistered', $name, $config );

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
 * Before and after filter list within search form.
 */
do_action( 'apd_before_filters' );
do_action( 'apd_after_filters' );

/**
 * After search query is modified.
 *
 * @param WP_Query $query          Query object.
 * @param array    $active_filters Active filter parameters.
 */
do_action( 'apd_search_query_modified', $query, $active_filters );
```

### Display & Templates

```php
/**
 * Archive page hooks (templates/archive-listing.php).
 */
do_action( 'apd_before_archive' );
do_action( 'apd_archive_wrapper_start' );
do_action( 'apd_before_archive_search_form' );
do_action( 'apd_after_archive_search_form' );
do_action( 'apd_before_archive_loop' );
do_action( 'apd_after_archive_loop' );
do_action( 'apd_archive_wrapper_end' );
do_action( 'apd_after_archive' );

/**
 * Single listing page hooks (templates/single-listing.php).
 */
do_action( 'apd_before_single_listing' );
do_action( 'apd_single_wrapper_start' );

/** @param int $listing_id Listing ID. */
do_action( 'apd_single_listing_start', $listing_id );
do_action( 'apd_single_listing_meta', $listing_id );
do_action( 'apd_single_listing_header', $listing_id );
do_action( 'apd_single_listing_image', $listing_id );
do_action( 'apd_single_listing_before_content', $listing_id );
do_action( 'apd_single_listing_after_content', $listing_id );
do_action( 'apd_single_listing_after_fields', $listing_id );
do_action( 'apd_single_listing_reviews', $listing_id );
do_action( 'apd_single_listing_sidebar_start', $listing_id );
do_action( 'apd_single_listing_contact_form', $listing_id );
do_action( 'apd_single_listing_sidebar_end', $listing_id );
do_action( 'apd_single_listing_end', $listing_id );

/** @param int $author_id Author user ID. @param int $listing_id Listing ID. */
do_action( 'apd_single_listing_author', $author_id, $listing_id );

/** @param int $listing_id Listing ID. */
do_action( 'apd_before_related_listings', $listing_id );
do_action( 'apd_after_related_listings', $listing_id );

do_action( 'apd_single_wrapper_end' );
do_action( 'apd_after_single_listing' );

/**
 * Listing card hooks (templates/listing-card.php, listing-card-list.php).
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_listing_card_start', $listing_id );
do_action( 'apd_listing_card_image', $listing_id );
do_action( 'apd_listing_card_body', $listing_id );
do_action( 'apd_listing_card_footer', $listing_id );
do_action( 'apd_listing_card_end', $listing_id );

/**
 * After a view is registered.
 *
 * @param ViewInterface $view View instance.
 */
do_action( 'apd_view_registered', $view );

/**
 * After a view is unregistered.
 *
 * @param string        $type View type name.
 * @param ViewInterface $view View instance.
 */
do_action( 'apd_view_unregistered', $type, $view );

/**
 * Views initialization (register custom views here).
 *
 * @param ViewRegistry $registry View registry instance.
 */
do_action( 'apd_views_init', $registry );
```

### Dashboard

```php
/**
 * Dashboard lifecycle hooks.
 *
 * @param array $args Dashboard template arguments.
 */
do_action( 'apd_before_dashboard', $args );
do_action( 'apd_dashboard_start', $args );
do_action( 'apd_dashboard_end', $args );

/**
 * After dashboard output is generated.
 *
 * @param string $output Dashboard HTML output.
 * @param array  $args   Dashboard template arguments.
 */
do_action( 'apd_after_dashboard', $output, $args );

/**
 * Before/after dashboard tab content.
 *
 * @param string $tab Current tab slug.
 */
do_action( 'apd_dashboard_before_content', $tab );

/**
 * After dashboard tab content.
 *
 * @param string $tab    Current tab slug.
 * @param string $output Tab HTML output.
 */
do_action( 'apd_dashboard_after_content', $tab, $output );

/**
 * Dynamic tab content hook.
 *
 * @param string $tab Current tab slug.
 */
do_action( "apd_dashboard_{$tab}_content", $tab );

/**
 * My Listings tab hooks.
 *
 * @param array $args My Listings template arguments.
 */
do_action( 'apd_my_listings_start', $args );
do_action( 'apd_my_listings_end', $args );

/**
 * Favorites tab hooks.
 *
 * @param array $args Favorites template arguments.
 */
do_action( 'apd_favorites_start', $args );
do_action( 'apd_favorites_end', $args );
```

### Profile

```php
/**
 * Profile template hooks.
 *
 * @param array $args Profile template arguments.
 */
do_action( 'apd_profile_start', $args );
do_action( 'apd_profile_end', $args );

/**
 * Before profile data is saved.
 *
 * @param array $data    Submitted profile data.
 * @param int   $user_id User ID.
 */
do_action( 'apd_before_save_profile', $data, $user_id );

/**
 * After profile data is saved.
 *
 * @param array $data    Submitted profile data.
 * @param int   $user_id User ID.
 */
do_action( 'apd_after_save_profile', $data, $user_id );

/**
 * After profile is saved successfully.
 *
 * @param int   $user_id User ID.
 * @param array $data    Submitted profile data.
 */
do_action( 'apd_profile_saved', $user_id, $data );

/**
 * When a custom avatar is uploaded.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $user_id      User ID.
 */
do_action( 'apd_avatar_uploaded', $attachment_id, $user_id );
```

### My Listings (Dashboard)

```php
/**
 * Before/after a listing is deleted from dashboard.
 *
 * @param int $listing_id Listing ID.
 * @param int $user_id    User ID.
 */
do_action( 'apd_before_delete_listing', $listing_id, $user_id );
do_action( 'apd_after_delete_listing', $listing_id, $user_id );

/**
 * Before/after a listing is trashed from dashboard.
 *
 * @param int $listing_id Listing ID.
 * @param int $user_id    User ID.
 */
do_action( 'apd_before_trash_listing', $listing_id, $user_id );
do_action( 'apd_after_trash_listing', $listing_id, $user_id );

/**
 * Before/after a listing status is changed from dashboard.
 *
 * @param int    $listing_id Listing ID.
 * @param string $status     New status.
 * @param string $old_status Previous status.
 * @param int    $user_id    User ID.
 */
do_action( 'apd_before_change_listing_status', $listing_id, $status, $old_status, $user_id );
do_action( 'apd_after_change_listing_status', $listing_id, $status, $old_status, $user_id );
```

### Favorites

```php
/**
 * Favorites initialization.
 *
 * @param Favorites $favorites Favorites instance.
 */
do_action( 'apd_favorites_init', $favorites );

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
 * Reviews initialization.
 *
 * @param ReviewManager $manager ReviewManager instance.
 */
do_action( 'apd_reviews_init', $manager );

/**
 * Before a review is created.
 *
 * @param array $comment_data Comment data for wp_insert_comment.
 * @param int   $listing_id   Listing ID.
 */
do_action( 'apd_before_review_create', $comment_data, $listing_id );

/**
 * After a review is created.
 *
 * @param int   $comment_id Comment ID.
 * @param int   $listing_id Listing ID.
 * @param array $data       Original review data.
 */
do_action( 'apd_review_created', $comment_id, $listing_id, $data );

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
 * Before a review is deleted.
 *
 * @param int  $review_id    Review ID.
 * @param bool $force_delete Whether to permanently delete.
 */
do_action( 'apd_before_review_delete', $review_id, $force_delete );

/**
 * After a review is deleted.
 *
 * @param int  $review_id    Review ID.
 * @param bool $force_delete Whether it was permanently deleted.
 */
do_action( 'apd_review_deleted', $review_id, $force_delete );

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

/**
 * Review template hooks (templates/review/).
 *
 * @param int $listing_id Listing ID.
 */
do_action( 'apd_before_reviews_section', $listing_id );
do_action( 'apd_after_reviews_section', $listing_id );
do_action( 'apd_reviews_section_start', $listing_id );
do_action( 'apd_reviews_section_after_header', $listing_id );
do_action( 'apd_reviews_section_before_form', $listing_id );
do_action( 'apd_reviews_section_after_form', $listing_id );
do_action( 'apd_reviews_section_end', $listing_id );
do_action( 'apd_before_review_form', $listing_id );
do_action( 'apd_after_review_form', $listing_id );

/** @param int $listing_id Listing ID. @param bool $is_edit_mode Whether editing. */
do_action( 'apd_review_form_start', $listing_id, $is_edit_mode );
do_action( 'apd_review_form_before_submit', $listing_id, $is_edit_mode );
do_action( 'apd_review_form_end', $listing_id, $is_edit_mode );

/** @param WP_Comment $review Review comment object. */
do_action( 'apd_review_item_footer', $review );
```

### Contact & Inquiries

```php
/**
 * Contact form initialization.
 */
do_action( 'apd_contact_form_init' );

/**
 * Contact handler initialization.
 */
do_action( 'apd_contact_handler_init' );

/**
 * After contact form fields in template.
 *
 * @param int    $listing_id Listing ID.
 * @param object $form       ContactForm instance.
 */
do_action( 'apd_contact_form_after_fields', $listing_id, $form );

/**
 * Before contact form email is sent.
 *
 * @param array   $data    Contact form data.
 * @param WP_Post $listing Listing post object.
 * @param WP_User $owner   Listing owner user object.
 */
do_action( 'apd_before_send_contact', $data, $listing, $owner );

/**
 * After contact form is successfully sent.
 *
 * @param array   $data    Contact form data.
 * @param WP_Post $listing Listing post object.
 * @param WP_User $owner   Listing owner user object.
 */
do_action( 'apd_contact_sent', $data, $listing, $owner );

/**
 * When an inquiry is logged.
 *
 * @param int     $inquiry_id Inquiry post ID.
 * @param array   $data       Inquiry data.
 * @param WP_Post $listing    Listing post object.
 */
do_action( 'apd_inquiry_logged', $inquiry_id, $data, $listing );

/**
 * Inquiry tracking initialization.
 */
do_action( 'apd_inquiry_tracker_init' );

/**
 * When an inquiry is marked as read/unread.
 *
 * @param int $inquiry_id Inquiry post ID.
 */
do_action( 'apd_inquiry_marked_read', $inquiry_id );
do_action( 'apd_inquiry_marked_unread', $inquiry_id );

/**
 * Before an inquiry is deleted.
 *
 * @param int  $inquiry_id   Inquiry post ID.
 * @param bool $force_delete Whether to permanently delete.
 */
do_action( 'apd_before_inquiry_delete', $inquiry_id, $force_delete );
```

### Email

```php
/**
 * Email manager initialization.
 */
do_action( 'apd_email_manager_init' );

/**
 * Before any email is sent.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $message Message body.
 * @param array  $headers Headers.
 * @param array  $context Email context (type, listing_id, etc.).
 */
do_action( 'apd_before_send_email', $to, $subject, $message, $headers, $context );

/**
 * After an email is sent.
 *
 * @param bool   $sent    Whether email was sent.
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param array  $context Email context.
 */
do_action( 'apd_after_send_email', $sent, $to, $subject, $context );
```

### REST API

```php
/**
 * After REST API controller is initialized.
 *
 * @param RestController $controller Controller instance.
 */
do_action( 'apd_rest_api_init', $controller );

/**
 * Before routes are registered (add custom endpoints here).
 *
 * @param RestController $controller Controller instance.
 */
do_action( 'apd_register_rest_routes', $controller );

/**
 * After all routes are registered.
 *
 * @param RestController $controller Controller instance.
 */
do_action( 'apd_rest_routes_registered', $controller );

/**
 * After an endpoint controller is registered/unregistered.
 *
 * @param string $name     Endpoint name.
 * @param object $endpoint Endpoint controller instance.
 */
do_action( 'apd_rest_endpoint_registered', $name, $endpoint );
do_action( 'apd_rest_endpoint_unregistered', $name );

/**
 * REST API CRUD hooks for listings.
 *
 * @param array           $post_data Post data array.
 * @param WP_REST_Request $request   REST request.
 */
do_action( 'apd_rest_before_create_listing', $post_data, $request );
do_action( 'apd_rest_after_create_listing', $listing_id, $request );
do_action( 'apd_rest_before_update_listing', $post_data, $listing, $request );
do_action( 'apd_rest_after_update_listing', $listing_id, $request );
do_action( 'apd_rest_before_delete_listing', $listing, $force, $request );
do_action( 'apd_rest_after_delete_listing', $listing_id, $force, $request );
```

### Settings

```php
/**
 * After settings are initialized.
 *
 * @param Settings $settings Settings instance.
 */
do_action( 'apd_settings_init', $settings );

/**
 * After settings are registered (add custom sections/fields).
 *
 * @param Settings $settings Settings instance.
 */
do_action( 'apd_register_settings', $settings );

/**
 * Before/after settings page renders.
 *
 * @param string $current_tab Current settings tab.
 */
do_action( 'apd_before_settings_page', $current_tab );
do_action( 'apd_after_settings_page', $current_tab );

/**
 * Before/after settings tab content.
 *
 * @param string $tab_id Tab identifier.
 */
do_action( 'apd_before_settings_tab', $tab_id );
do_action( 'apd_after_settings_tab', $tab_id );
```

### Blocks

```php
/**
 * Block system initialization (register custom blocks here).
 *
 * @param BlockManager $manager Block manager instance.
 */
do_action( 'apd_blocks_init', $manager );

/**
 * After a block is registered.
 *
 * @param AbstractBlock $block Block instance.
 * @param string        $name  Block name.
 */
do_action( 'apd_block_registered', $block, $name );

/**
 * Before/after a specific block renders.
 *
 * @param array $attributes Block attributes.
 */
do_action( "apd_before_block_{$name}", $attributes );
do_action( "apd_after_block_{$name}", $attributes );
```

### Shortcodes

```php
/**
 * Shortcode system initialization (register custom shortcodes here).
 *
 * @param ShortcodeManager $manager Shortcode manager instance.
 */
do_action( 'apd_shortcodes_init', $manager );

/**
 * After a shortcode is registered.
 *
 * @param AbstractShortcode $shortcode Shortcode instance.
 * @param string            $tag       Shortcode tag.
 */
do_action( 'apd_shortcode_registered', $shortcode, $tag );

/**
 * Before/after a specific shortcode renders.
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content.
 */
do_action( "apd_before_shortcode_{$tag}", $atts, $content );
do_action( "apd_after_shortcode_{$tag}", $output, $atts, $content );

/**
 * User registration hook.
 *
 * @param int    $user_id  Created user ID.
 * @param string $username Username.
 * @param string $email    Email address.
 */
do_action( 'apd_user_registered', $user_id, $username, $email );
```

### Submission Form Templates

```php
/**
 * Submission form template hooks (templates/submission/submission-form.php).
 *
 * @param array $config     Form configuration.
 * @param int   $listing_id Listing ID (0 for new).
 */
do_action( 'apd_submission_form_start', $config, $listing_id );
do_action( 'apd_submission_form_after_basic_fields', $config, $listing_id );
do_action( 'apd_submission_form_after_custom_fields', $config, $listing_id );
do_action( 'apd_submission_form_after_taxonomies', $config, $listing_id );
do_action( 'apd_submission_form_after_image', $config, $listing_id );
do_action( 'apd_submission_form_before_submit', $config, $listing_id );
do_action( 'apd_submission_form_end', $config, $listing_id );

/**
 * Before/after submission form renders (PHP class hooks).
 *
 * @param array $args Form arguments.
 */
do_action( 'apd_before_submission_form', $args );

/**
 * @param string $output Form HTML output.
 * @param array  $args   Form arguments.
 */
do_action( 'apd_after_submission_form', $output, $args );

/**
 * After successful submission (template hook).
 *
 * @param int    $listing_id Listing ID.
 * @param string $status     Listing status.
 * @param bool   $is_update  Whether this was an update.
 */
do_action( 'apd_after_submission_success', $listing_id, $status, $is_update );
```

### Modules

```php
/**
 * Fires when modules can register (fires at init priority 1).
 *
 * @param ModuleRegistry $registry Module registry instance.
 */
do_action( 'apd_modules_init', $registry );

/**
 * After all modules are loaded.
 *
 * @param ModuleRegistry $registry Module registry instance.
 */
do_action( 'apd_modules_loaded', $registry );

/**
 * After a module is registered/unregistered.
 *
 * @param string $slug   Module slug.
 * @param array  $config Module configuration.
 */
do_action( 'apd_module_registered', $slug, $config );
do_action( 'apd_module_unregistered', $slug, $config );
```

### Demo Data

```php
/**
 * Demo data page initialization.
 *
 * @param DemoDataPage $page Page instance.
 */
do_action( 'apd_demo_data_init', $page );

/**
 * Before/after demo data generation.
 */
do_action( 'apd_before_generate_demo_data' );

/**
 * @param array $results Generation results with counts.
 */
do_action( 'apd_after_generate_demo_data', $results );

/**
 * Before/after demo data deletion.
 */
do_action( 'apd_before_delete_demo_data' );

/**
 * @param array $counts Deleted item counts by type.
 */
do_action( 'apd_after_delete_demo_data', $counts );
```

### Performance & Caching

```php
/**
 * When category cache is invalidated.
 */
do_action( 'apd_category_cache_invalidated' );

/**
 * When listing caches are invalidated.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
do_action( 'apd_listing_cache_invalidated', $post_id, $post );

/**
 * When all plugin caches are cleared.
 *
 * @param int $deleted Number of deleted transients.
 */
do_action( 'apd_cache_cleared', $deleted );
```

### Templates

```php
/**
 * Before/after a template is loaded.
 *
 * @param string $template_name Template name.
 * @param string $template      Located template path.
 * @param array  $args          Template arguments.
 */
do_action( 'apd_before_get_template', $template_name, $template, $args );
do_action( 'apd_after_get_template', $template_name, $template, $args );
```

### AJAX

```php
/**
 * Before/after AJAX filter request is processed.
 */
do_action( 'apd_before_ajax_filter' );
do_action( 'apd_after_ajax_filter' );
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
 * @param array  $field      Field configuration.
 * @return mixed Modified value.
 */
add_filter( 'apd_listing_field_value', function( $value, $listing_id, $field_name, $field ) {
    if ( $field_name === 'price' ) {
        return (float) $value;
    }
    return $value;
}, 10, 4 );

/**
 * Modify listing card display data.
 *
 * @param array $data       Card data.
 * @param int   $listing_id Listing post ID.
 * @return array Modified data.
 */
add_filter( 'apd_listing_card_data', function( $data, $listing_id ) {
    $data['custom_badge'] = get_post_meta( $listing_id, '_custom_badge', true );
    return $data;
}, 10, 2 );

/**
 * Modify listing card CSS classes.
 *
 * @param array $classes    Card CSS classes.
 * @param int   $listing_id Listing post ID.
 * @return array Modified classes.
 */
add_filter( 'apd_listing_card_classes', function( $classes, $listing_id ) {
    return $classes;
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
 * @param array $args Query arguments.
 * @return array Modified arguments.
 */
add_filter( 'apd_search_query_args', function( $args ) {
    // Custom query modifications
    return $args;
});

/**
 * Modify searchable meta keys.
 *
 * @param array $meta_keys Meta keys included in search.
 * @return array Modified meta keys.
 */
add_filter( 'apd_searchable_meta_keys', function( $meta_keys ) {
    $meta_keys[] = '_apd_custom_field';
    return $meta_keys;
});

/**
 * Modify available orderby options.
 *
 * @param array $options Orderby options.
 * @return array Modified options.
 */
add_filter( 'apd_orderby_options', function( $options ) {
    return $options;
});
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
 * @param string $status  Default status.
 * @param int    $user_id Current user ID.
 * @return string Modified status.
 */
add_filter( 'apd_submission_default_status', function( $status, $user_id ) {
    return current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
}, 10, 2 );

/**
 * Filter status for edited listings.
 *
 * @param string $status     Status to set.
 * @param int    $listing_id Listing ID.
 * @param int    $user_id    User ID.
 * @return string Modified status.
 */
add_filter( 'apd_edit_listing_status', function( $status, $listing_id, $user_id ) {
    return $status;
}, 10, 3 );

/**
 * Filter whether user can submit listings.
 *
 * @param bool $can_submit Whether user can submit.
 * @param int  $user_id    User ID.
 * @return bool Modified value.
 */
add_filter( 'apd_user_can_submit_listing', function( $can_submit, $user_id ) {
    return $can_submit;
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

/**
 * Filter whether admin notification is sent on submission.
 *
 * @param bool  $notify     Whether to notify admin.
 * @param int   $listing_id Listing ID.
 * @param array $data       Submitted data.
 * @return bool Modified value.
 */
add_filter( 'apd_submission_admin_notification', function( $notify, $listing_id, $data ) {
    return $notify;
}, 10, 3 );

/**
 * Modify redirect URL after successful submission.
 *
 * @param string $url        Redirect URL.
 * @param int    $listing_id Listing ID.
 * @param bool   $is_update  Whether this was an update.
 * @return string Modified URL.
 */
add_filter( 'apd_submission_success_redirect', function( $url, $listing_id, $is_update ) {
    return $url;
}, 10, 3 );

/**
 * Modify redirect URL after submission error.
 *
 * @param string   $url    Redirect URL.
 * @param WP_Error $errors Validation errors.
 * @return string Modified URL.
 */
add_filter( 'apd_submission_error_redirect', function( $url, $errors ) {
    return $url;
}, 10, 2 );
```

### Spam Protection

```php
/**
 * Bypass spam protection for trusted users.
 *
 * @param bool $bypass  Whether to bypass.
 * @param int  $user_id Current user ID.
 * @return bool Modified value.
 */
add_filter( 'apd_bypass_spam_protection', function( $bypass, $user_id ) {
    return current_user_can( 'edit_others_posts' );
}, 10, 2 );

/**
 * Custom spam check (e.g., reCAPTCHA).
 *
 * @param bool  $passed  Whether spam check passed.
 * @param array $data    Submitted data ($_POST).
 * @param int   $user_id Current user ID.
 * @return bool Modified value.
 */
add_filter( 'apd_submission_spam_check', function( $passed, $data, $user_id ) {
    return $passed && verify_recaptcha( $data['g-recaptcha-response'] );
}, 10, 3 );

/**
 * Modify honeypot field name.
 *
 * @param string $name Honeypot field name.
 * @return string Modified name.
 */
add_filter( 'apd_honeypot_field_name', function( $name ) {
    return 'website_url'; // Default
});

/**
 * Modify rate limit settings.
 */
add_filter( 'apd_submission_rate_limit', function( $limit ) {
    return 5; // Max submissions per period
});

add_filter( 'apd_submission_rate_period', function( $seconds ) {
    return 3600; // Per hour
});

add_filter( 'apd_submission_min_time', function( $seconds ) {
    return 3; // Minimum seconds to submit form
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
 * Modify all fields when retrieved.
 *
 * @param array $fields All registered fields.
 * @param array $args   Query arguments.
 * @return array Modified fields.
 */
add_filter( 'apd_get_fields', function( $fields, $args ) {
    return $fields;
}, 10, 2 );

/**
 * Modify field validation result.
 *
 * @param bool|WP_Error        $result     Validation result.
 * @param mixed                $value      Field value.
 * @param array                $field      Field config.
 * @param string               $context    Validation context.
 * @param FieldTypeInterface   $field_type Field type handler.
 * @return bool|WP_Error Modified result.
 */
add_filter( 'apd_validate_field', function( $result, $value, $field, $context, $field_type ) {
    return $result;
}, 10, 5 );

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
 * Modify rendered field display HTML (single listing).
 *
 * @param string $html       Rendered HTML.
 * @param array  $field      Field config.
 * @param mixed  $value      Field value.
 * @param int    $listing_id Listing ID.
 * @return string Modified HTML.
 */
add_filter( 'apd_render_field_display', function( $html, $field, $value, $listing_id ) {
    return $html;
}, 10, 4 );

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
 * @param array          $options Filter options.
 * @param AbstractFilter $filter  Filter instance.
 * @return array Modified options.
 */
add_filter( 'apd_filter_options', function( $options, $filter ) {
    return $options;
}, 10, 2 );

/**
 * Modify rendered filter HTML.
 *
 * @param string $output Rendered filter HTML.
 * @param array  $filter Filter configuration.
 * @param mixed  $value  Current filter value.
 * @param array  $request Request data.
 * @return string Modified HTML.
 */
add_filter( 'apd_render_filter', function( $output, $filter, $value, $request ) {
    return $output;
}, 10, 4 );
```

### Templates

```php
/**
 * Modify template path lookup.
 *
 * @param string $located  Located template path.
 * @param string $template Template name.
 * @return string Modified path.
 */
add_filter( 'apd_locate_template', function( $located, $template ) {
    return $located;
}, 10, 2 );

/**
 * Modify archive page title.
 *
 * @param string $title Archive title.
 * @return string Modified title.
 */
add_filter( 'apd_archive_title', function( $title ) {
    return $title;
});

/**
 * Modify archive page description.
 *
 * @param string $description Archive description.
 * @return string Modified description.
 */
add_filter( 'apd_archive_description', function( $description ) {
    return $description;
});

/**
 * Modify pagination arguments.
 *
 * @param array    $args  Pagination arguments.
 * @param WP_Query $query Current query.
 * @return array Modified arguments.
 */
add_filter( 'apd_pagination_args', function( $args, $query ) {
    return $args;
}, 10, 2 );
```

### Shortcodes

```php
/**
 * Modify shortcode attributes.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $tag     Shortcode tag.
 * @param string $content Shortcode content.
 * @return array Modified attributes.
 */
add_filter( 'apd_shortcode_{tag}_atts', function( $atts, $tag, $content ) {
    $atts['columns'] = 4;
    return $atts;
}, 10, 3 );

/**
 * Modify shortcode output.
 *
 * @param string $output  Shortcode HTML output.
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 * @return string Modified output.
 */
add_filter( 'apd_shortcode_{tag}_output', function( $output, $atts, $content ) {
    return '<div class="custom-wrapper">' . $output . '</div>';
}, 10, 3 );
```

### Blocks

```php
/**
 * Modify block arguments before render.
 *
 * @param array         $args  Block arguments.
 * @param AbstractBlock $block Block instance.
 * @return array Modified arguments.
 */
add_filter( 'apd_block_{name}_args', function( $args, $block ) {
    return $args;
}, 10, 2 );

/**
 * Modify block output after render.
 *
 * @param string $output     Block HTML output.
 * @param array  $attributes Block attributes.
 * @return string Modified output.
 */
add_filter( 'apd_block_{name}_output', function( $output, $attributes ) {
    return $output;
}, 10, 2 );

/**
 * Modify listings block query arguments.
 *
 * @param array $query_args WP_Query arguments.
 * @param array $attributes Block attributes.
 * @return array Modified arguments.
 */
add_filter( 'apd_listings_block_query_args', function( $query_args, $attributes ) {
    return $query_args;
}, 10, 2 );

/**
 * Modify block editor data.
 *
 * @param array $data Editor localization data.
 * @return array Modified data.
 */
add_filter( 'apd_blocks_editor_data', function( $data ) {
    return $data;
});
```

### Dashboard

```php
/**
 * Modify dashboard tabs.
 *
 * @param array $tabs    Dashboard tabs.
 * @param int   $user_id Current user ID.
 * @return array Modified tabs.
 */
add_filter( 'apd_dashboard_tabs', function( $tabs, $user_id ) {
    $tabs['custom'] = [
        'label' => 'Custom Tab',
        'callback' => 'render_custom_tab',
    ];
    return $tabs;
}, 10, 2 );

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

/**
 * Modify dashboard template arguments.
 *
 * @param array $args Dashboard arguments.
 * @return array Modified arguments.
 */
add_filter( 'apd_dashboard_args', function( $args ) {
    return $args;
});

/**
 * Modify dashboard HTML output.
 *
 * @param string $output Dashboard HTML.
 * @param array  $args   Dashboard arguments.
 * @return string Modified HTML.
 */
add_filter( 'apd_dashboard_html', function( $output, $args ) {
    return $output;
}, 10, 2 );

/**
 * Modify dashboard page URL.
 *
 * @param string $url Dashboard URL.
 * @return string Modified URL.
 */
add_filter( 'apd_dashboard_url', function( $url ) {
    return $url;
});

/**
 * Modify My Listings query arguments.
 *
 * @param array $query_args WP_Query arguments.
 * @param int   $user_id    User ID.
 * @return array Modified arguments.
 */
add_filter( 'apd_my_listings_query_args', function( $query_args, $user_id ) {
    return $query_args;
}, 10, 2 );

/**
 * Modify My Listings row actions.
 *
 * @param array   $actions Available actions.
 * @param WP_Post $post    Listing post object.
 * @return array Modified actions.
 */
add_filter( 'apd_my_listings_actions', function( $actions, $post ) {
    return $actions;
}, 10, 2 );

/**
 * Filter whether user can permanently delete a listing.
 *
 * @param bool $can_delete  Whether user can delete.
 * @param int  $listing_id  Listing ID.
 * @param int  $user_id     User ID.
 * @return bool Modified value.
 */
add_filter( 'apd_user_can_delete_listing', function( $can_delete, $listing_id, $user_id ) {
    return $can_delete;
}, 10, 3 );

/**
 * Modify favorites page query arguments.
 *
 * @param array $query_args WP_Query arguments.
 * @param int   $user_id    User ID.
 * @return array Modified arguments.
 */
add_filter( 'apd_favorites_page_query_args', function( $query_args, $user_id ) {
    return $query_args;
}, 10, 2 );
```

### Profile

```php
/**
 * Modify profile template arguments.
 *
 * @param array $args Profile arguments.
 * @return array Modified arguments.
 */
add_filter( 'apd_profile_args', function( $args ) {
    return $args;
});

/**
 * Modify profile user data.
 *
 * @param array $data    User data.
 * @param int   $user_id User ID.
 * @return array Modified data.
 */
add_filter( 'apd_profile_user_data', function( $data, $user_id ) {
    return $data;
}, 10, 2 );

/**
 * Modify profile validation errors.
 *
 * @param WP_Error $errors  Validation errors.
 * @param array    $data    Submitted data.
 * @param int      $user_id User ID.
 * @return WP_Error Modified errors.
 */
add_filter( 'apd_validate_profile', function( $errors, $data, $user_id ) {
    return $errors;
}, 10, 3 );

/**
 * Modify user social links.
 *
 * @param array $links   Social link definitions.
 * @param int   $user_id User ID.
 * @return array Modified links.
 */
add_filter( 'apd_user_social_links', function( $links, $user_id ) {
    return $links;
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

/**
 * Modify favorite button CSS classes.
 *
 * @param array $classes     CSS classes.
 * @param int   $listing_id  Listing ID.
 * @param bool  $is_favorite Whether listing is favorited.
 * @return array Modified classes.
 */
add_filter( 'apd_favorite_button_classes', function( $classes, $listing_id, $is_favorite ) {
    return $classes;
}, 10, 3 );

/**
 * Modify favorite button HTML.
 *
 * @param string $html       Button HTML.
 * @param int    $listing_id Listing ID.
 * @param array  $args       Button arguments.
 * @return string Modified HTML.
 */
add_filter( 'apd_favorite_button_html', function( $html, $listing_id, $args ) {
    return $html;
}, 10, 3 );
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
 * Modify review comment data before saving.
 *
 * @param array $comment_data Comment data for wp_insert_comment.
 * @param int   $listing_id   Listing ID.
 * @param array $data         Original review data.
 * @return array Modified comment data.
 */
add_filter( 'apd_review_data', function( $comment_data, $listing_id, $data ) {
    return $comment_data;
}, 10, 3 );

/**
 * Set default review status.
 *
 * @param string $status Default status ('pending' or 'approved').
 * @return string Modified status.
 */
add_filter( 'apd_review_default_status', function( $status ) {
    return current_user_can( 'moderate_comments' ) ? 'approved' : 'pending';
});

/**
 * Set number of reviews per page.
 *
 * @param int $per_page Reviews per page.
 * @return int Modified count.
 */
add_filter( 'apd_reviews_per_page', function( $per_page ) {
    return 10;
});

/**
 * Filter whether author can review own listing.
 *
 * @param bool $can_review  Whether author can review.
 * @param int  $listing_id  Listing ID.
 * @param int  $user_id     User ID.
 * @return bool Modified value.
 */
add_filter( 'apd_author_can_review_own_listing', function( $can_review, $listing_id, $user_id ) {
    return $can_review;
}, 10, 3 );

/**
 * Control whether the review form is shown.
 *
 * @param bool $can_show    Whether to show the form.
 * @param int  $listing_id  Listing ID.
 * @return bool Modified value.
 */
add_filter( 'apd_can_show_review_form', function( $can_show, $listing_id ) {
    return $can_show;
}, 10, 2 );

/**
 * Filter whether user can edit a review.
 *
 * @param bool       $can_edit   Whether user can edit.
 * @param int        $review_id  Review comment ID.
 * @param int        $user_id    User ID.
 * @param WP_Comment $review     Review comment object.
 * @return bool Modified value.
 */
add_filter( 'apd_user_can_edit_review', function( $can_edit, $review_id, $user_id, $review ) {
    return $can_edit;
}, 10, 4 );

/**
 * Modify review guidelines text.
 *
 * @param string $text Default guidelines text.
 * @return string Modified text.
 */
add_filter( 'apd_review_guidelines_text', function( $text ) {
    return $text;
});
```

### Contact Form

```php
/**
 * Control which listings can receive contact.
 *
 * @param bool    $can_receive Whether listing can receive contact.
 * @param int     $listing_id  Listing ID.
 * @param WP_Post $listing     Listing post object.
 * @return bool Modified value.
 */
add_filter( 'apd_listing_can_receive_contact', function( $can_receive, $listing_id, $listing ) {
    return $can_receive;
}, 10, 3 );

/**
 * Modify contact form validation errors.
 *
 * @param WP_Error $errors Validation errors.
 * @param array    $data   Form data.
 * @return WP_Error Modified errors.
 */
add_filter( 'apd_contact_validation_errors', function( $errors, $data ) {
    return $errors;
}, 10, 2 );

/**
 * Modify contact email recipient.
 *
 * @param string  $to      Recipient email.
 * @param array   $data    Form data.
 * @param WP_Post $listing Listing post object.
 * @return string Modified email.
 */
add_filter( 'apd_contact_email_to', function( $to, $data, $listing ) {
    return $to;
}, 10, 3 );

/**
 * Modify contact email subject.
 *
 * @param string  $subject Email subject.
 * @param array   $data    Form data.
 * @param WP_Post $listing Listing post object.
 * @return string Modified subject.
 */
add_filter( 'apd_contact_email_subject', function( $subject, $data, $listing ) {
    return $subject;
}, 10, 3 );

/**
 * Modify contact email message body.
 *
 * @param string  $message Email message.
 * @param array   $data    Form data.
 * @param WP_Post $listing Listing post object.
 * @return string Modified message.
 */
add_filter( 'apd_contact_email_message', function( $message, $data, $listing ) {
    return $message;
}, 10, 3 );

/**
 * Modify contact email headers.
 *
 * @param array   $headers Email headers.
 * @param array   $data    Form data.
 * @param WP_Post $listing Listing post object.
 * @return array Modified headers.
 */
add_filter( 'apd_contact_email_headers', function( $headers, $data, $listing ) {
    return $headers;
}, 10, 3 );

/**
 * Control whether admin receives a copy of contact emails.
 *
 * @param bool $send_copy Whether to send admin copy.
 * @return bool Modified value.
 */
add_filter( 'apd_contact_send_admin_copy', '__return_true' );

/**
 * Modify admin email for contact copies.
 *
 * @param string $email Admin email.
 * @return string Modified email.
 */
add_filter( 'apd_contact_admin_email', function( $email ) {
    return $email;
});

/**
 * Contact form spam protection filters.
 */
add_filter( 'apd_contact_bypass_spam_protection', function( $bypass, $user_id ) {
    return $bypass;
}, 10, 2 );

add_filter( 'apd_contact_spam_check', function( $passed, $data, $user_id ) {
    return $passed;
}, 10, 3 );

add_filter( 'apd_contact_honeypot_field_name', function( $name ) {
    return 'contact_website'; // Default
});

add_filter( 'apd_contact_min_time', function( $seconds ) {
    return 2; // Minimum seconds
});

add_filter( 'apd_contact_rate_limit', function( $limit ) {
    return 10; // Max per period
});

add_filter( 'apd_contact_rate_period', function( $seconds ) {
    return 3600; // Per hour
});
```

### Email

```php
/**
 * Modify email recipient.
 *
 * @param string $to      Recipient.
 * @param string $subject Email subject.
 * @param array  $context Email context.
 * @return string Modified recipient.
 */
add_filter( 'apd_email_to', function( $to, $subject, $context ) {
    return $to;
}, 10, 3 );

/**
 * Modify email subject.
 *
 * @param string $subject Email subject.
 * @param string $to      Recipient.
 * @param array  $context Email context.
 * @return string Modified subject.
 */
add_filter( 'apd_email_subject', function( $subject, $to, $context ) {
    return $subject;
}, 10, 3 );

/**
 * Modify email message body.
 *
 * @param string $message Message body.
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param array  $context Email context.
 * @return string Modified message.
 */
add_filter( 'apd_email_message', function( $message, $to, $subject, $context ) {
    return $message;
}, 10, 4 );

/**
 * Modify email headers.
 *
 * @param array  $headers Email headers.
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param array  $context Email context.
 * @return array Modified headers.
 */
add_filter( 'apd_email_headers', function( $headers, $to, $subject, $context ) {
    return $headers;
}, 10, 4 );

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
 * Customize email from name and address.
 */
add_filter( 'apd_email_from_name', function( $name ) {
    return 'My Directory';
});

add_filter( 'apd_email_from_email', function( $email ) {
    return 'noreply@example.com';
});

/**
 * Customize email colors.
 */
add_filter( 'apd_email_header_color', function( $color ) {
    return '#1a73e8';
});

add_filter( 'apd_email_header_text_color', function( $color ) {
    return '#ffffff';
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

### Modules

```php
/**
 * Modify module configuration before registration.
 *
 * @param array  $config Module config.
 * @param string $slug   Module slug.
 * @return array Modified config.
 */
add_filter( 'apd_register_module_config', function( $config, $slug ) {
    return $config;
}, 10, 2 );

/**
 * Modify module on retrieval.
 *
 * @param array  $config Module config.
 * @param string $slug   Module slug.
 * @return array Modified config.
 */
add_filter( 'apd_get_module', function( $config, $slug ) {
    return $config;
}, 10, 2 );

/**
 * Modify modules list on retrieval.
 *
 * @param array $modules All modules.
 * @param array $args    Query arguments.
 * @return array Modified modules.
 */
add_filter( 'apd_get_modules', function( $modules, $args ) {
    return $modules;
}, 10, 2 );
```

### Display & Views

```php
/**
 * Modify view container CSS classes.
 *
 * @param array         $classes CSS classes.
 * @param AbstractView  $view    View instance.
 * @return array Modified classes.
 */
add_filter( 'apd_view_container_classes', function( $classes, $view ) {
    return $classes;
}, 10, 2 );

/**
 * Modify listing template arguments within a view.
 *
 * @param array        $args       Template arguments.
 * @param int          $listing_id Listing ID.
 * @param AbstractView $view       View instance.
 * @return array Modified arguments.
 */
add_filter( 'apd_view_listing_args', function( $args, $listing_id, $view ) {
    return $args;
}, 10, 3 );

/**
 * Control whether to skip view count for admin users.
 *
 * @param bool $skip Whether to skip admin view counting.
 * @return bool Modified value.
 */
add_filter( 'apd_skip_admin_view_count', '__return_true' );

/**
 * Control whether to load frontend assets on current page.
 *
 * @param bool $should_load Whether to load assets.
 * @return bool Modified value.
 */
add_filter( 'apd_should_load_frontend_assets', function( $should_load ) {
    return $should_load;
});

/**
 * Modify frontend/admin JavaScript localization data.
 *
 * @param array $data Script data.
 * @return array Modified data.
 */
add_filter( 'apd_frontend_script_data', function( $data ) {
    return $data;
});

add_filter( 'apd_admin_script_data', function( $data ) {
    return $data;
});
```

### REST API

```php
/**
 * Modify REST API listing query arguments.
 *
 * @param array           $args    WP_Query arguments.
 * @param WP_REST_Request $request REST request.
 * @return array Modified arguments.
 */
add_filter( 'apd_rest_listings_query_args', function( $args, $request ) {
    return $args;
}, 10, 2 );

/**
 * Modify REST API listing response data.
 *
 * @param array           $data    Listing data.
 * @param WP_Post         $listing Listing post.
 * @param WP_REST_Request $request REST request.
 * @return array Modified data.
 */
add_filter( 'apd_rest_listing_data', function( $data, $listing, $request ) {
    return $data;
}, 10, 3 );

/**
 * Modify REST API review response data.
 *
 * @param array      $data   Review data.
 * @param WP_Comment $review Review comment.
 * @return array Modified data.
 */
add_filter( 'apd_rest_review_data', function( $data, $review ) {
    return $data;
}, 10, 2 );

/**
 * Modify REST API inquiry response data.
 *
 * @param array   $data    Inquiry data.
 * @param WP_Post $inquiry Inquiry post.
 * @return array Modified data.
 */
add_filter( 'apd_rest_inquiry_data', function( $data, $inquiry ) {
    return $data;
}, 10, 2 );

/**
 * Modify REST API taxonomy term response data.
 *
 * @param array   $data Term data.
 * @param WP_Term $term Term object.
 * @return array Modified data.
 */
add_filter( 'apd_rest_term_data', function( $data, $term ) {
    return $data;
}, 10, 2 );
```

### Performance

```php
/**
 * Modify cache expiration time.
 *
 * @param int    $expiration Expiration in seconds (default 3600).
 * @param string $key        Cache key.
 * @return int Modified expiration.
 */
add_filter( 'apd_cache_expiration', function( $expiration, $key ) {
    return $expiration;
}, 10, 2 );
```

### Demo Data

```php
/**
 * Modify default demo data quantities.
 *
 * @param array $defaults Default counts.
 * @return array Modified defaults.
 */
add_filter( 'apd_demo_default_counts', function( $defaults ) {
    return $defaults;
});

/**
 * Modify demo category hierarchy data.
 *
 * @param array $categories Category data.
 * @return array Modified categories.
 */
add_filter( 'apd_demo_category_data', function( $categories ) {
    return $categories;
});

/**
 * Modify demo listing data before creation.
 *
 * @param array  $listing_data  Listing data.
 * @param string $category_slug Category slug.
 * @param int    $index         Listing index.
 * @return array Modified listing data.
 */
add_filter( 'apd_demo_listing_data', function( $listing_data, $category_slug, $index ) {
    return $listing_data;
}, 10, 3 );
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

## WP-CLI Commands

All Purpose Directory provides WP-CLI commands for managing demo data without the browser.

### Demo Data

```bash
# Generate all demo data with default quantities
wp apd demo generate

# Generate with custom quantities
wp apd demo generate --users=10 --listings=50

# Generate only specific data types
wp apd demo generate --types=categories,tags,listings

# Show current demo data counts
wp apd demo status

# Show counts as JSON
wp apd demo status --format=json

# Delete all demo data (with confirmation)
wp apd demo delete

# Delete without confirmation prompt
wp apd demo delete --yes
```

### Generate Options

| Option | Default | Description |
|--------|---------|-------------|
| `--types=<types>` | `all` | Comma-separated list of data types to generate. Options: `users`, `categories`, `tags`, `listings`, `reviews`, `inquiries`, `favorites`, `all` |
| `--users=<count>` | `5` | Number of demo users to create (max 20) |
| `--tags=<count>` | `10` | Number of tags to create (max 10) |
| `--listings=<count>` | `25` | Number of listings to create (max 100) |

### Status Options

| Option | Default | Description |
|--------|---------|-------------|
| `--format=<format>` | `table` | Output format: `table`, `json`, `csv`, `yaml` |

### Delete Options

| Option | Description |
|--------|-------------|
| `--yes` | Skip the confirmation prompt |

### Notes

- Reviews require listings to exist (2-4 per listing, automatically generated).
- Inquiries require listings (0-2 per listing, random).
- Favorites require both listings and users.
- Module providers (from external modules) are automatically included in generate, delete, and status commands.
- All demo data is tracked with the `_apd_demo_data` meta key for clean removal.

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
