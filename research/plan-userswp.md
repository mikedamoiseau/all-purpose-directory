# UsersWP Plugin - Feature Analysis

**Plugin Name:** UsersWP - Front-end Login Form, User Registration, User Profile & Members Directory
**Version:** 1.2.54
**Author:** AyeCode Ltd
**License:** GPLv2+
**Requirements:** WordPress 6.1+, PHP 5.6+

**Description:** A lightweight, user-friendly WordPress plugin for front-end user profiles, user registration, login forms, and user directory management with comprehensive page builder integration.

---

## User Authentication & Access Control

### Authentication Features
- Front-end login form with custom redirect capabilities
- User registration with multiple customizable registration forms
- Password recovery (forgot password) form
- Reset password functionality
- Change password form (for logged-in users)
- Account settings/edit profile form
- User account deletion functionality
- Email activation/verification system
- Role-based login redirects
- Auto-approve and auto-login options
- WordPress 2FA integration
- GDPR acceptance during registration
- Terms & Conditions checkbox

## User Profiles

### Profile Features
- Customizable user profile pages with cover image and avatar
- User profile header with cover image, avatar, and user information
- Profile image cropping functionality
- Banner/Cover image management
- User badge system

### Profile Tabs
- User's posts (with post count)
- User's comments (with comment count)
- Custom fields display
- GeoDirectory listings (if GD installed)
- Reviews and ratings
- Friends/Followers (with addon)
- Activity feed (with addon)
- Dashboard/Account settings access

## Users Directory

### Directory Features
- Front-end users listing/directory page
- User search functionality
- User filtering by role
- User sorting options (name, registration date, random, custom fields)
- Pagination for users list
- User list item templates
- Exclude specific users from directory
- User loop with customizable display

## Form Builder

### Form Features
- Drag-and-drop form builder with visual interface
- Multiple registration forms (unlimited)
- Form-specific user role assignment

### Custom Field Types
- Text input
- Textarea
- Select dropdown
- Multiselect dropdown
- Checkbox
- Radio buttons
- Date field
- File upload
- Country selector
- User role selector
- HTML editor field
- Button group field
- Fieldset (grouped fields)

### Field Configuration
- Field validation patterns
- Field placeholder support
- Required/optional fields
- Field privacy settings (public/private/let user decide)
- Custom field default values
- Field icons
- Fieldset display as own profile tab

## Shortcodes & Blocks

### Widget/Block IDs
| Shortcode | Description |
|-----------|-------------|
| `[uwp_login]` | Login form or logged-in dashboard |
| `[uwp_register]` | Registration form |
| `[uwp_account]` | Account/Edit profile form |
| `[uwp_profile]` | Full user profile display |
| `[uwp_users]` | Users directory listing |
| `[uwp_forgot]` | Forgot password form |
| `[uwp_reset]` | Reset password form |
| `[uwp_change]` | Change password form |
| `[uwp_profile_header]` | Profile header (cover, avatar, user info) |
| `[uwp_profile_tabs]` | Profile tabs navigation and content |
| `[uwp_author_box]` | Author box display |
| `[uwp_users_search]` | Users search form |
| `[uwp_users_loop]` | Users loop/list output |
| `[uwp_users_loop_actions]` | Users search and sorting controls |
| `[uwp_user_avatar]` | User avatar image only |
| `[uwp_user_cover]` | User cover image only |
| `[uwp_user_post_counts]` | User post/comment counts |
| `[uwp_user_meta]` | Display specific user meta fields |
| `[uwp_user_title]` | User display name/title |
| `[uwp_user_actions]` | User action buttons |
| `[uwp_user_badge]` | User badge display |
| `[uwp_profile_actions]` | Profile action buttons |
| `[uwp_profile_social]` | Social links display |
| `[uwp_profile_section]` | Custom profile sections |

## Email Notifications

### Email Types
- New user registration
- Email verification/activation
- Password reset requests
- Password change confirmation
- Account update confirmation
- Delete account notification
- New user notification from WordPress

### Email Features
- Template tags for dynamic content
- Email HTML templates with header/footer
- Admin notification settings
- User email notification preferences (mute options)
- Email from address customization

## Page Builder Integration

### Supported Page Builders
- Elementor
- Oxygen
- Divi
- Beaver Builder
- Gutenberg
- Breakdance

### Design Features
- Bootstrap UI (AUI) integration
- Template override support via theme child themes
- Custom CSS classes on widgets/blocks
- Responsive design with mobile-first approach
- Margin and padding controls for widgets
- Border and shadow styling options
- Login/Register lightbox modal option
- Password strength indicator

## Admin Features

### Admin Menu Structure
- UsersWP Settings (settings page)
- Form Builder
- User Types
- Tools
- Status
- Add-ons

### Settings Tabs
- General
- Profile Tabs
- Emails
- User Sorting
- Redirects
- Import/Export
- Uninstall
- Add-ons

## Settings Sections

### General Settings
- Pages configuration
- Register settings
- Login settings
- Change password settings
- Profile settings
- Users directory settings
- Account settings
- Author box settings
- Developer settings

## Third-Party Integrations

### Built-in GeoDirectory Integration
- Display user's GeoDirectory listings in profile tab
- Show listing count in user profile
- Reviews tab for listings
- Favorites tab for liked listings
- Listing author actions

### Premium Addon Integrations
- WooCommerce (orders, reviews)
- bbPress (forum interactions)
- Easy Digital Downloads (purchases, downloads)
- WP Job Manager (job listings)
- MailChimp (newsletter subscription)
- MailerLite (newsletter subscription)
- MailPoet (newsletter subscription)
- myCRED (points/gamification)
- WP Fusion (user meta compatibility)
- SEOPress (profile page meta)
- Yoast SEO (author archives, profile meta)
- WPML (multilingual support)
- Polylang (multilingual support)

## Security Features

### Security Measures
- Nonce verification for forms
- User capability checks
- GDPR compliance support
- Privacy policy integration
- Data export functionality
- User consent/terms acceptance
- Honeypot field detection
- Password strength requirements
- Username and email validation
- Input sanitization

## User Management

### User Features
- User types/roles system
- Custom user roles via registration forms
- User role field in forms
- User sorting preferences
- User exclusion from directory
- Pending email verification status
- Admin bar hiding based on role
- Admin access restriction based on role
- Multisite user management

## Technical Architecture

### Core Classes
- UsersWP - Core plugin class
- UsersWP_Forms - Form handling
- UsersWP_Profile - Profile functionality
- UsersWP_Meta - User metadata
- UsersWP_Pages - Page management
- UsersWP_Form_Builder - Form builder UI
- UsersWP_Admin_Menus - Admin menu structure
- UsersWP_Mails - Email handling
- UsersWP_Ajax - AJAX handlers
- UsersWP_Templates - Template loading

### Database
- Stores settings in wp_options as serialized array (uwp_settings)
- Custom user meta with 'uwp_' prefix
- Custom usermeta table for profile data

## Premium Add-on Ecosystem (20+)

### Available Add-ons
- Membership management
- Dashboard customization
- Private messaging between users
- Discussion groups/communities
- Profile completion progress tracking
- Advanced search filtering
- Social features (followers, friends)
- Content moderation
- Email newsletter integrations
- Notification systems
- Verified user badges
