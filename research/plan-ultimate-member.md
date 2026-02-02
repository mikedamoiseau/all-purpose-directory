# Ultimate Member Plugin - Feature Analysis

**Plugin Name:** Ultimate Member
**Version:** 2.11.1
**Author:** suspended
**License:** GPL
**Requirements:** WordPress 6.2+, PHP 7.0+

**Description:** A membership and community plugin that enables users to create powerful online communities with user profiles, registration/login, member directories, and content restriction features.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Forms | `um_form` | Registration, login, and profile forms |
| Directories | `um_directory` | Member directory configurations |

## User Management & Authentication

- Front-end user registration forms
- Front-end user login forms
- Front-end user profiles
- Custom user roles with granular capabilities
- Password reset functionality
- Account management pages
- User logout functionality
- Multi-site compatibility

## Form System

### Form Builder
- Drag-and-drop form builder (Admin)
- Custom form fields with conditional logic
- Form types: Register, Login, Profile
- GDPR-compliant registration forms
- Pre-built form templates

### Form Features
- Security features including honeypot field detection
- Form validation and error handling
- Conditional form field logic
- Emoji support in forms
- File upload handling

## Content Management

### Member Directory
- Member directory/community members listing
- Multiple views (grid, list)
- Pagination
- Search functionality

### Content Restriction
- Content restriction by role and user type
- Post-level access control
- Taxonomy/Term-level access control
- Blog page-level restrictions
- Restricted content redirect handling

## User Profiles

- Customizable user profile pages
- User cover photos
- User profile photos
- User bio/description fields
- Author posts display on profile
- Author comments display on profile
- Profile tabs support
- User location integration (via extension)
- User bookmark system (via extension)

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[ultimatemember]` | Main shortcode |
| `[ultimatemember_login]` | Login form |
| `[ultimatemember_register]` | Registration form |
| `[ultimatemember_profile]` | User profile display |
| `[ultimatemember_directory]` | Member directory listing |
| `[um_loggedin]` | Show content to logged-in users |
| `[um_loggedout]` | Show content to logged-out users |
| `[um_show_content]` | Role-based content restriction |
| `[ultimatemember_searchform]` | Member search form |
| `[um_author_profile_link]` | Author profile link |

## Gutenberg Blocks

- **Account block** - Display account page for current user
- **Form block** - Display specific forms
- **Member Directory block** - Display member directory
- **Password Reset block** - Display password reset form
- Custom block category: um-blocks

## Widgets

- **UM Search Widget** - Member search widget

## Admin Features

### Dashboard & Management
- Main Ultimate Member dashboard
- Settings page with multiple tabs
- Forms management
- User Roles management
- Member Directories management
- User overview statistics

### Utilities
- Temp file purge utility
- User cache management
- Extension upgrade management

## Settings & Configuration

- Configurable permalink base
- Display name options
- Member directory settings
- Privacy options for directories
- Rate limiting for AJAX actions
- User role and capability management
- Email notification configuration
- Custom permissions system

## Email System

- Email notifications for various events
- Customizable email templates
- Email template paths extensibility
- Support for Mandrill integration
- Email dispatch via Action Scheduler
- Email preview functionality

## AJAX Functionality

- Forms AJAX handlers
- Pages AJAX handlers
- Users AJAX handlers
- Security AJAX handlers
- Rate limiting for nopriv AJAX actions
- Admin AJAX hooks system

## Privacy & GDPR

- GDPR compliance features
- Privacy policy integration
- Data export functionality
- User consent/terms acceptance
- Restricted content login-to-view templates

## Frontend Templates

- Account page template
- Login page template
- Register page template
- Logout page template
- Profile page template
- Member directory templates (grid, list)
- Password change template
- Password reset template
- Search form template
- Restricted content templates
- Modal templates for popups

## Security Features

- Nonce verification for AJAX
- User capability checks
- Input sanitization and validation
- Security headers (X-Frame-Options)
- Rate limiting on AJAX endpoints
- Permission filtering for account forms
- Honeypot field detection

## Cron & Scheduling

- Action Scheduler integration
- Event scheduling system
- Email dispatch scheduling
- Account status check scheduling
- Deactivation-triggered event cleanup

## Developer Features

### Customization
- Extensive hook system (actions and filters)
- Extension API
- Custom form field types support
- Template override system
- Custom role creation
- Filter-based menu items

### Architecture
- Singleton pattern main class
- Service locator pattern for components
- PSR-4 style namespace-based autoloader
- Namespaces: um\*, um\core\*, um\admin\*, um\frontend\*

### File Management
- File uploader system
- Temporary file management
- File validation
- Directory file operations
- EXIF data handling

## Integration Points

- Support for multiple extensions
- Template customization system
- Theme compatibility checks
- Page builder integration
- Custom CSS/styling options
- REST API integration (v1 and v2)

## Premium Extensions Available

### Communication
- Private Messages
- Real-time Notifications
- Notices

### Social Features
- Social Login
- Followers
- Friends
- Groups
- Social Activity

### Profile Enhancements
- User Photos
- User Bookmarks
- Verified Users
- User Reviews
- User Tags
- User Locations
- Profile Tabs
- Profile Completeness
- Unsplash

### E-commerce & Payments
- Stripe
- WooCommerce
- myCRED

### Security & Compliance
- Google reCAPTCHA
- Terms & Conditions

### Integrations
- MailChimp
- bbPress
- Private Content

## Technical Implementation

### Database
- Leverages WordPress post meta and user meta
- Custom tables for some features

### Configuration
- Class-config.php manages core forms, directories, pages, and metadata
- Dedicated Options class for WordPress options

### Action Scheduler
- Built-in async task queue system

### Template System
- Multiple template paths with override capabilities
- Theme-compatible template loading
