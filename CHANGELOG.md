# Changelog

All notable changes to All Purpose Directory will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project structure and planning documentation
- CLAUDE.md with development guidelines
- Research analysis of 30+ competitor directory plugins
- `.distignore` file for WordPress.org distribution packaging
- `languages/` directory for translations
- `apd_listing` custom post type with full REST API support
- Custom `expired` post status for listings
- `Capabilities::get_listing_caps()` for post type capability mapping
- Integration tests for post type registration (12 tests)
- Admin columns for listing post type (thumbnail, category, status badge, views count)
- Sortable admin columns (views count, status)
- Color-coded status badges in admin list (publish, pending, draft, expired)
- CSS styles for admin list table enhancements
- `AdminColumns::increment_views()` and `AdminColumns::get_views()` utility methods
- Integration tests for admin columns (8 new tests)

### Changed
- Updated "Tested up to" version to WordPress 6.9 in README.txt
- Added ABSPATH direct access protection to all PHP files
- Removed deprecated `load_plugin_textdomain()` call (handled by WordPress.org since WP 4.6)
- Fixed output escaping in Activator.php version requirement messages

### Deprecated
- Nothing yet

### Removed
- Nothing yet

### Fixed
- Nothing yet

### Security
- Nothing yet
