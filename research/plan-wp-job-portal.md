# WP Job Portal Plugin - Feature Analysis

**Plugin Name:** WP Job Portal
**Version:** 2.4.6
**Author:** WP Job Portal
**License:** GPLv3
**Requirements:** WordPress 5.5+, PHP 7.4+, Tested up to 6.9

**Description:** A lightweight, AI-powered job board plugin for WordPress that delivers a complete recruitment system with intelligent automation for matching candidates to jobs, resume parsing, and AI-driven filters with smart job suggestions and resume recommendations.

---

## Custom Database Tables (30+)

### Core Entities
- `wp_wj_portal_job` - Job listings
- `wp_wj_portal_resume` - Resumes
- `wp_wj_portal_company` - Companies
- `wp_wj_portal_users` - Plugin users

### Job Related
- `wp_wj_portal_jobtype` - Job types
- `wp_wj_portal_jobstatus` - Job statuses
- `wp_wj_portal_jobapply` - Job applications
- `wp_wj_portal_jobshortlist` - Job shortlists
- `wp_wj_portal_jobcities` - Job-city associations
- `wp_wj_portal_category` - Job categories (230+ pre-built)

### Location Related
- `wp_wj_portal_country` - Countries
- `wp_wj_portal_state` - States/Provinces
- `wp_wj_portal_city` - Cities
- `wp_wj_portal_companycities` - Company-city associations

### Configuration & Settings
- `wp_wj_portal_config` - Plugin configuration
- `wp_wj_portal_fieldsordering` - Field ordering
- `wp_wj_portal_department` - Departments
- `wp_wj_portal_emailtemplate` - Email templates

## Job Management

### Job Features
- Unlimited job posting from front-end
- 30+ fields for detailed job information
- Job publish workflow with admin approval
- Job statuses (Draft, Published, Active, Closed, Expired)
- Job types (Full-time, Part-time, Contract, Temporary, Freelance)
- Multiple job shifts support
- Salary ranges with multiple currencies
- Experience levels (Entry, Mid, Senior, Executive)
- Career level matching
- Education requirements
- Department assignment
- Job categories with unlimited nested levels
- Job copy functionality
- Jobs on map display (Google Maps integration)

### Job Application Methods
- Standard job application with saved resumes
- Quick Apply - upload resume without account
- Allow visitors to apply without accounts
- Resume shortlisting from applications
- Application status tracking (Inbox, Shortlisted, Hired, Spam)
- Applicant rating and internal notes

## Resume Management

### Resume Features
- Resume creation and submission
- Multiple resumes per user (configurable)
- Resume sections: Personal info, addresses, education, employers, skills, languages
- Custom resume sections
- Resume builder with advanced fields
- Resume shortlisting by employers
- Resume folder organization
- Resume search for employers
- Resume rating and comments
- Resume tagging system
- Resume PDF generation
- Resume export as CSV

## Company Management

### Company Features
- Employer company profile creation
- Multiple companies per employer [Addon]
- Company details page
- Company logo upload
- Company category assignment
- Company city/location
- Company contact information
- Company approval workflow
- Featured company listing

## User Dashboards

### Job Seeker Dashboard
- Resume management
- Applied jobs tracking
- Job shortlists
- Activity log
- Statistics
- Profile completion indicator
- Multiple resume creation

### Employer Dashboard
- Job posting and management
- Job applicant management
- Resume review and management
- Company management
- Department management
- Activity log
- Statistics
- Folder organization

## Shortcodes (25+ Base, 45+ with Addons)

| Shortcode | Description |
|-----------|-------------|
| `[wpjobportal_employer_controlpanel]` | Employer dashboard |
| `[wpjobportal_jobseeker_controlpanel]` | Job seeker dashboard |
| `[wpjobportal_job_search]` | Job search form and results |
| `[wpjobportal_job]` | Display job listings |
| `[wpjobportal_job_categories]` | Display job categories |
| `[wpjobportal_job_types]` | Display job types |
| `[wpjobportal_my_appliedjobs]` | User's applied jobs |
| `[wpjobportal_my_companies]` | Employer's companies |
| `[wpjobportal_my_departments]` | Employer's departments |
| `[wpjobportal_my_jobs]` | Employer's posted jobs |
| `[wpjobportal_my_resumes]` | Job seeker's resumes |
| `[wpjobportal_add_company]` | Company creation form |
| `[wpjobportal_add_job]` | Job posting form |
| `[wpjobportal_add_resume]` | Resume creation form |
| `[wpjobportal_employer_registration]` | Employer registration |
| `[wpjobportal_jobseeker_registration]` | Job seeker registration |
| `[wpjobportal_registration]` | Generic registration |
| `[wpjobportal_login_page]` | Login form |
| `[wpjobportal_jobsbycategory]` | Jobs by category |
| `[wpjobportal_jobsbycities]` | Jobs by cities |
| `[wpjobportal_jobsonmap]` | Jobs on map display |

## Search Features

### Job Search
- Advanced search with multiple criteria
- Search by category, location, salary, experience
- Search by career level, education
- AI Job Search addon for context-aware searching
- AI Suggested Jobs for personalized recommendations

### Resume Search
- Resume search with multiple criteria
- Search by keywords, location, experience
- AI Resume Search for intelligent filtering
- AI Suggested Resumes for employer recommendations

## Field Management

### Field Configuration
- Advanced field ordering interface
- Field visibility control per role
- Required/optional configuration
- Field publication rules
- Custom field creation
- Conditional logic for field display
- Field types: Text, Textarea, Select, Multi-select, Checkbox, Radio, File, Date, Email, Number, URL

## Payment & Monetization

### Credit System
- Free Mode - unlimited listings
- Per Listing Mode - charge per posting
- Membership Mode - package-based limits
- Credit tracking and management

### Payment Gateways
- PayPal integration
- Stripe integration
- WooCommerce integration
- Credit pack purchasing
- Package system with tiers
- Invoice generation
- Transaction logging

## Email Notifications

### Email Types
- Job application alerts
- Job approval notifications
- Registration emails
- Membership subscription emails
- Reservation management emails
- Message notifications
- Review notifications
- Payment/Package expiration

### Email Features
- Customizable email templates (45+ in premium)
- HTML templates with customization
- Dynamic email fields

## Integrations

### Third-Party Plugins
- WP Job Manager migration support
- Import companies, jobs, resumes, applications

### Elementor Integration
- Elementor widgets for all shortcodes
- Elementor color customization
- Elementor typography controls
- Visual builder support

## AJAX Handlers (80+)

### Key Functions
- Job/Resume data population
- Shortlist operations
- Quick view functionality
- Apply operations
- Email operations
- File operations
- Notification management
- Configuration operations
- Payment processing

## Security Features

### Security Measures
- reCAPTCHA v3 support
- NONCE verification for forms
- Input sanitization and validation
- SQL injection prevention
- XSS attack prevention
- Patchstack VDP integration

## AI-Powered Features (Addons)

### AI Addons
- AI Search Job - Context-aware job search
- AI Search Resume - Intelligent resume filtering
- AI Suggested Jobs - Personalized job recommendations
- AI Suggested Resumes - Smart resume suggestions

## Premium Addons (20+)

- Job Alert Notifications
- Featured Entities
- Apply as Visitor
- Advanced Resume Builder
- Credit System
- Message System
- Resume Export
- Folder Management
- Cover Letters
- Reports
- Cron Job
- Widgets
- Tell A Friend
- Tags
- Resume Search
- RSS Feeds
- Social Share
- Multi Company
- Multi Resume
- Multi Department
- Visitor Submit Jobs/Resumes

## Technical Implementation

### Architecture
- Custom MVC Framework
- 468 PHP Files
- Database-Centric design (custom tables)
- Singleton pattern for global class

### Core Classes & Modules
- `wpjobportal` - Main plugin class
- `WPJOBPORTALajax` - AJAX handling
- `WPJOBPORTALshortcodes` - Shortcode registration
- `wpjobportaladmin` - Admin menu setup

### Configuration System
- Configuration stored in custom table
- Settings organized by entity type, user role, feature area
- Role-based access control with visibility rules

### Session Management
- Custom session handling for non-logged-in users
- Cookie-based tracking for social login
- Session data storage with expiration

## Admin Menu Structure

### Main Menu Items
- Dashboard
- Jobs
- Resume
- Companies
- Configurations

### Hidden Menu Items
- Theme Settings
- PDF Settings
- Departments
- Categories
- Salary Range
- Users
- Email Templates
- Countries
- Career Levels
- Cities
- Currency
- Custom Fields
- Packages (if Credits addon active)
- Messages (if Message addon active)
