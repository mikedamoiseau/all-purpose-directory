# Directorist Plugin - Feature Analysis

**Plugin Name:** Directorist - Business Directory Plugin with Classified Ads Listings
**Version:** 8.5.8
**Author:** wpWax
**License:** GPL
**Requirements:** WordPress 4.6+, PHP 7.0+

**Description:** A comprehensive solution to create professional directory sites of any kind (Yelp, Foursquare, classifieds, real estate, job boards, etc.)

---

## Custom Post Types & Taxonomies

### Post Types
| Post Type | Slug | Description |
|-----------|------|-------------|
| Listings | `at_biz_dir` | Main listing post type |
| Orders | `atbdp_orders` | Payment/orders tracking |
| Custom Fields | `atbdp_fields` | Custom field definitions |

### Taxonomies
| Taxonomy | Slug | Description |
|----------|------|-------------|
| Categories | `at_biz_dir-category` | Hierarchical listing categories |
| Locations | `at_biz_dir-location` | Geographic location taxonomy |
| Tags | `at_biz_dir-tags` | Non-hierarchical listing tags |
| Directory Types | `atbdp_listing_types` | Multi-directory separation |

## Listing Management Features

- Multi-directory support (create multiple directory types in one site)
- Unlimited custom listings per directory
- Front-end listing submission form with drag-and-drop builder
- Back-end listing management via WordPress admin
- CSV import/export functionality for bulk listing management
- Featured listings option with visibility priority
- Listing expiration date management
- Listing status management (published, draft, expired, pending review)
- Guest listing submission (without login requirement)
- Listing owner contact form
- Quick edit and bulk edit capabilities
- Listing view count tracking and statistics
- Open Street Map and Google Maps integration
- Listing image slider/carousel functionality
- Video embedding support
- Single listing page builder with customizable sections
- Schema markup support for rich snippets/SEO

## Search & Filtering Features

- Advanced search form with custom filters
- AJAX-powered instant search functionality
- Radius/geolocation search (by distance from location)
- Custom field filtering and search
- Category and location dropdown filtering
- Taxonomy hierarchy support for filtering
- Search results pagination (includes infinite scroll)
- Search result layout customization (grid/list/map views)
- Search form builder (drag-and-drop customization)
- Multiple directory type filtering
- Price range slider filtering
- Zipcode radius search capability

## User & Author Management

- User registration and login system
- Author profile pages with listing showcase
- User dashboard for managing personal listings
- User preferences/settings management
- Author approval workflow (pending/approved author system)
- Become an author functionality
- Email verification for user registration
- User password recovery
- User role and capability mapping
- Custom user meta fields
- Author listing statistics
- All authors directory listing

## Monetization & Payment Features

### Payment Gateways
- Offline/Manual payments (built-in)
- PayPal integration (premium extension)
- Stripe integration (premium extension)
- Authorize.Net integration (premium extension)

### Monetization Options
- Paid listings (per submission fee)
- Featured listings (premium visibility)
- Subscription plans (recurring billing)
- WooCommerce integration for payment flexibility
- Pricing plans feature
- Order management system
- Tax calculation support
- Transaction failure handling
- Order tracking and history

## Custom Field Types (25+)

- Text, Textarea, Email, URL
- Number (with min/max/step controls)
- Checkbox, Radio, Switch, Select
- Date, Time
- File Upload, Image Upload
- Color Picker
- Map Field
- Video Field
- Pricing Field
- Categories, Locations, Tags (taxonomy fields)
- Social Info Field
- View Count Field
- Description Field

## Shortcodes (35+)

### Archive Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[directorist_all_listing]` | Display all listings |
| `[directorist_category]` | Single category listings |
| `[directorist_location]` | Single location listings |
| `[directorist_tag]` | Single tag listings |

### Taxonomy Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[directorist_all_categories]` | All categories listing |
| `[directorist_all_locations]` | All locations listing |

### Search Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[directorist_search_listing]` | Search form |
| `[directorist_search_result]` | Search results display |

### User/Author Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[directorist_user_dashboard]` | User management dashboard |
| `[directorist_author_profile]` | Author profile page |
| `[directorist_all_authors]` | All authors directory |
| `[directorist_signin_signup]` | Combined sign in/sign up |
| `[directorist_custom_registration]` | Custom registration form |
| `[directorist_user_login]` | Login form |

### Listing & Checkout Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[directorist_add_listing]` | Frontend listing submission form |
| `[directorist_checkout]` | Payment checkout page |
| `[directorist_payment_receipt]` | Payment confirmation |
| `[directorist_transaction_failure]` | Payment failure page |

## Gutenberg Blocks (16+)

- Account Button
- Add Listing Form
- Authors Listing
- Categories
- Checkout
- Dashboard
- Listing Form
- Listings Grid/List
- Locations
- Payment Receipt
- Search Form
- Search Modal
- Search Results
- Single Category/Location/Tag
- Single Listing
- Signin/Signup
- Transaction Failure

## Elementor Widgets (25+)

- All Listings Widget
- All Categories Widget
- All Locations Widget
- Single Category/Location Widget
- Search Form Widget
- Search Results Widget
- Add Listing Widget
- Author Profile Widget
- All Authors Widget
- Login Form Widget
- Custom Registration Widget
- Single Listing Widget
- Checkout Widget
- Payment Receipt Widget

## Other Page Builder Support

- **Bricks Builder:** Native Directorist blocks integration
- **Oxygen Builder:** Integration support (premium extension)
- **BuddyBoss:** Community integration (premium extension)
- **BuddyPress:** Social networking integration (premium extension)

## Reviews & Ratings System

- Comment-based review system
- Star rating display (1-5 stars)
- Average rating calculation per listing
- Review moderation capabilities
- Guest reviews (optional)
- Email notifications for new reviews
- Review metadata storage
- Schema markup support for reviews
- Admin review management interface
- Front-end review submission form

## REST API

### Version 1 Endpoints
- Listings controller (CRUD operations)
- Listings actions controller
- Plans controller
- Orders controller
- Users controller
- Users account controller
- Users favorites controller
- Listing reviews controller
- Builder controller
- Temporary media upload controller

### Version 2 Endpoints
- Listings controller (v2 improvements)

## WordPress Widgets

- Right Sidebar for Listings (custom sidebar)
- All Categories Widget
- All Locations Widget
- Featured Listings Widget
- Popular Listings Widget
- Submit Listing Widget
- Search Form Widget
- Login Form Widget
- Contact Form Widget
- Author Info Widget
- Video Widget
- Similar Listings Widget
- Single Map Widget

## Email System

- Email notifications for listing submissions
- Review/rating notifications
- Payment/order confirmations
- User registration confirmations
- Email template customization
- HTML and plain text support
- Email variable replacement
- Admin/Author/User notification emails

## SEO Features

- Built-in SEO optimization
- Yoast SEO compatibility
- Meta title and description customization
- Sitemap support
- Schema markup support (structured data)
- Open Graph meta tags
- Twitter Card support
- Canonical URL support
- Breadcrumb support

## Multilingual & Translation

- Full translation support (WPML, Polylang, Loco Translate)
- RTL (Right-to-Left) language support
- Text domain: 'directorist'
- Multi-language custom field support

## Security Features

- Nonce verification for forms
- Input sanitization
- Output escaping
- Capability checking
- Role-based access control
- SQL prepared statements
- File upload validation
- reCAPTCHA support
- Email verification for users

## Performance Features

- Asset minification support
- Lazy loading options
- Database query optimization
- Caching system
- Background image processing
- Thumbnail caching
- Script loading optimization
- Conditional asset loading

## Additional Features

- **Favorites/Wishlist:** Users can save favorite listings
- **View Tracking:** Track and display listing views
- **Badges:** Display status badges (new, popular, featured, open/closed)
- **Responsive Design:** Fully responsive for all devices
- **Layout Options:** Grid, list, and map view options
- **Related Listings:** Show related listings on single listing page
- **Reports System:** Report abuse functionality
- **System Status:** Detailed system information for troubleshooting

## Premium Extensions (30+)

- Universal Search (multi-directory search)
- Search Alert (notification system)
- Pricing Plans (subscription management)
- Booking (appointment scheduling)
- Payment Gateways (PayPal, Stripe, Authorize.Net)
- Claim Listings (business verification)
- Listings with Map
- Gallery/Image Gallery
- Business Hours
- Listing FAQs
- Social Login
- Mark as Sold
- Live Chat
- Slider & Carousel
- Compare Listings
- Oxygen Integration
- BuddyBoss/BuddyPress Integration
- Directory Linking
- Mailchimp Integration
- GamiPress Integration
- WPML Integration
- Digital Marketplace
- Google reCAPTCHA

## Plugin Architecture

### Core Classes
- `Directorist_Base` - Main plugin singleton
- Models (data handling)
- Data stores (persistence layer)
- Modules (feature-based organization)

### Extensibility
- 100+ hooks (actions and filters)
- Template override system
- REST API for third-party integration
- Well-structured object-oriented code
- Namespaced classes
- Easy to extend and customize
