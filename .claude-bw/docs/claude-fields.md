# Field System Reference

This document covers the Field Registry, Field Renderer, Field Validator, and available field types.

## Field Registry

The Field Registry (`src/Fields/FieldRegistry.php`) manages custom listing fields. Fields are registered with configuration and stored in post meta with the `_apd_` prefix.

### Field Configuration Structure

```php
[
    'name'        => 'field_name',      // Unique identifier (auto-sanitized)
    'type'        => 'text',            // Field type (text, email, select, etc.)
    'label'       => 'Field Label',     // Display label (auto-generated from name if empty)
    'description' => 'Help text',       // Help text shown below field
    'required'    => false,             // Whether field is required
    'default'     => '',                // Default value
    'placeholder' => '',                // Placeholder text
    'options'     => [],                // Options for select/radio/checkbox types
    'validation'  => [],                // Validation rules (min_length, max_length, pattern, callback)
    'searchable'  => false,             // Include in search queries
    'filterable'  => false,             // Show in filter UI
    'admin_only'  => false,             // Hide from frontend
    'priority'    => 10,                // Display order (lower = earlier)
    'class'       => '',                // Additional CSS classes
    'attributes'  => [],                // Additional HTML attributes
]
```

### Field Registration Functions

```php
apd_register_field( string $name, array $config = [] ): bool
apd_unregister_field( string $name ): bool
apd_get_field( string $name ): ?array
apd_get_fields( array $args = [] ): array  // Filter by type, searchable, filterable, admin_only
apd_has_field( string $name ): bool
```

### Field Type Functions

```php
apd_register_field_type( FieldTypeInterface $field_type ): bool
apd_get_field_type( string $type ): ?FieldTypeInterface
```

### Listing Field Functions

```php
apd_get_listing_field( int $listing_id, string $field_name, mixed $default = '' ): mixed
apd_set_listing_field( int $listing_id, string $field_name, mixed $value ): int|bool
apd_get_field_meta_key( string $field_name ): string  // Returns '_apd_{field_name}'
```

### Field Registry Hooks

- Filters: `apd_register_field_config`, `apd_get_field`, `apd_get_fields`, `apd_listing_field_value`, `apd_set_listing_field_value`
- Actions: `apd_field_type_registered`, `apd_field_registered`, `apd_field_unregistered`

---

## Field Renderer

The Field Renderer (`src/Fields/FieldRenderer.php`) handles rendering fields for admin meta boxes, frontend submission forms, and display contexts.

### Render Contexts

- `admin` - Admin meta box rendering with full access to all fields
- `frontend` - Frontend forms, excludes `admin_only` fields
- `display` - Read-only display (single listing view), uses `formatValue()`

### Field Renderer Functions

```php
apd_field_renderer(): FieldRenderer  // Get renderer instance
apd_render_field( string $name, mixed $value, string $context, int $listing_id ): string
apd_render_fields( array $values, array $args, string $context, int $listing_id ): string
apd_render_admin_fields( int $listing_id, array $args = [] ): string
apd_render_frontend_fields( int $listing_id = 0, array $args = [] ): string
apd_render_display_fields( int $listing_id, array $args = [] ): string
apd_register_field_group( string $group_id, array $config ): void
apd_unregister_field_group( string $group_id ): void
apd_set_field_errors( array|WP_Error $errors ): void
apd_clear_field_errors(): void
```

### Field Group Configuration

```php
apd_register_field_group( 'contact', [
    'title'       => 'Contact Information',
    'description' => 'How to reach you',
    'priority'    => 10,          // Display order (lower = earlier)
    'collapsible' => true,        // Allow collapse/expand
    'collapsed'   => false,       // Initial state
    'fields'      => ['email', 'phone', 'website'],
]);
```

### Field Renderer Hooks

- Filters: `apd_field_wrapper_class`, `apd_render_field`, `apd_render_field_display`, `apd_field_group_wrapper_class`, `apd_render_field_group`, `apd_render_display_fields`, `apd_should_display_field`
- Actions: `apd_after_admin_fields`, `apd_after_frontend_fields`

### Conditional Display Example

```php
// Hide field based on custom logic
add_filter( 'apd_should_display_field', function( $display, $field, $context, $listing_id ) {
    if ( $field['name'] === 'premium_feature' && ! user_has_premium() ) {
        return false;
    }
    return $display;
}, 10, 4 );
```

---

## Field Validator

The Field Validator (`src/Fields/FieldValidator.php`) handles validation and sanitization of field values. It coordinates validation across multiple fields, delegates to field type validators, and aggregates errors.

### Validation Context

- `form` - Default context for form submissions
- `admin` - Admin panel validation
- `frontend` - Frontend form validation
- `api` - REST API validation

### Field Validator Functions

```php
apd_field_validator(): FieldValidator  // Get validator instance
apd_validate_field( string $name, mixed $value, bool $sanitize = true ): bool|WP_Error
apd_validate_fields( array $values, array $args = [] ): bool|WP_Error
apd_sanitize_field( string $name, mixed $value ): mixed
apd_sanitize_fields( array $values, array $args = [] ): array
apd_process_fields( array $values, array $args = [] ): array  // Returns [valid, values, errors]
```

### Validation Arguments

```php
apd_validate_fields( $values, [
    'fields'            => ['name', 'email'],  // Only validate these fields
    'exclude'           => ['phone'],          // Skip these fields
    'sanitize'          => true,               // Sanitize before validation
    'skip_unregistered' => true,               // Ignore unknown fields
]);
```

### Process Fields (Sanitize + Validate)

```php
$result = apd_process_fields( $_POST );
if ( $result['valid'] ) {
    // Use $result['values'] (sanitized)
    save_listing( $result['values'] );
} else {
    // Display $result['errors']
    apd_set_field_errors( $result['errors'] );
}
```

### Field Validator Hooks

- Filters: `apd_before_validate_field`, `apd_validate_field`, `apd_sanitized_fields`
- Actions: `apd_after_validate_fields`

### Custom Validation Example

```php
// Add cross-field validation
add_action( 'apd_after_validate_fields', function( $errors, $values, $args, $context ) {
    if ( ! empty( $values['end_date'] ) && $values['end_date'] < $values['start_date'] ) {
        $errors->add( 'end_date', 'End date must be after start date.' );
    }
}, 10, 4 );
```

---

## Admin Meta Box

The Listing Meta Box (`src/Admin/ListingMetaBox.php`) handles the custom fields meta box on the `apd_listing` post edit screen.

### Meta Box Configuration

- ID: `apd_listing_fields`
- Screen: `apd_listing` post type
- Context: `normal`
- Priority: `high`

### Security

- Nonce action: `apd_save_listing_fields`
- Nonce field: `apd_fields_nonce`
- Capability check: `edit_apd_listing`
- Autosave: skipped

### Save Process

1. Nonce verification
2. Autosave check (skips during autosave)
3. Capability check (`edit_apd_listing`)
4. Post type verification
5. `apd_before_listing_save` action fires
6. Fields processed via `apd_process_fields()` (sanitize + validate)
7. Each field saved via `apd_set_listing_field()`
8. `apd_after_listing_save` action fires

### Hooks

```php
// Before fields are saved (modify values before processing)
add_action( 'apd_before_listing_save', function( $post_id, $values ) {
    // $values are raw extracted values from POST
}, 10, 2 );

// After fields are saved (trigger related actions)
add_action( 'apd_after_listing_save', function( $post_id, $sanitized_values ) {
    // $sanitized_values are the processed/saved values
}, 10, 2 );
```

---

## Available Field Types

All field types are in `src/Fields/Types/` and extend `AbstractFieldType`:

| Type | Class | Features | Notes |
|------|-------|----------|-------|
| `text` | TextField | searchable, sortable | HTML5 text input with maxlength/pattern attributes |
| `textarea` | TextareaField | searchable | Multi-line, configurable rows, nl2br formatting |
| `richtext` | RichTextField | searchable | wp_editor WYSIWYG, wp_kses_post sanitization |
| `number` | NumberField | filterable, sortable | Integer with min/max/step validation |
| `decimal` | DecimalField | filterable, sortable | Float with configurable precision |
| `currency` | CurrencyField | filterable, sortable | Symbol position, thousand separators |
| `email` | EmailField | searchable, sortable | is_email validation, mailto links |
| `url` | UrlField | searchable | FILTER_VALIDATE_URL, external link handling |
| `phone` | PhoneField | searchable | 7-15 digit validation, tel: links |
| `date` | DateField | filterable, sortable | HTML5 date, min/max range, date_i18n formatting |
| `time` | TimeField | sortable | HTML5 time, H:i/H:i:s validation |
| `datetime` | DateTimeField | sortable | datetime-local input |
| `daterange` | DateRangeField | filterable | Start/end pair, JSON storage |
| `select` | SelectField | filterable | Single dropdown, option validation |
| `multiselect` | MultiSelectField | filterable, repeater | Multiple selection, JSON storage |
| `checkbox` | CheckboxField | filterable | Boolean, stores '1'/'0' |
| `checkboxgroup` | CheckboxGroupField | filterable, repeater | Multiple checkboxes, accessible fieldset |
| `radio` | RadioField | filterable | Radio group, accessible fieldset/legend |
| `switch` | SwitchField | filterable | Toggle with role="switch" |
| `file` | FileField | - | Media library integration, attachment ID |
| `image` | ImageField | - | Image upload with thumbnail preview |
| `gallery` | GalleryField | repeater | Multiple images, drag-drop sorting |
| `color` | ColorField | - | HTML5 color picker, hex validation |
| `hidden` | HiddenField | - | Hidden input, always valid |

### Type-Specific Config Options

```php
// Textarea
['rows' => 5]

// Number/Decimal/Currency
['min' => 0, 'max' => 100, 'step' => 1, 'precision' => 2]
['currency_symbol' => '$', 'currency_position' => 'before', 'allow_negative' => false]

// Date/Time/DateTime
['min' => '2024-01-01', 'max' => '2025-12-31', 'date_format' => 'F j, Y']

// Select/Radio/Checkbox Group
['options' => ['value1' => 'Label 1', 'value2' => 'Label 2'], 'empty_option' => 'Select...']

// File/Image/Gallery
['allowed_types' => ['pdf', 'doc'], 'max_size' => 5242880, 'preview_size' => 'thumbnail', 'max_images' => 10]

// Switch
['on_label' => 'Yes', 'off_label' => 'No']

// RichText
['media_buttons' => true, 'textarea_rows' => 10, 'teeny' => false, 'quicktags' => true]
```
