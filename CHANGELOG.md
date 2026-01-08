# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## 1.0.0 – 2025-10-21

### Added
- Initial release
- Application for tracking the working hours of company employees
- User time tracking functionality
- Admin panel for managing users and settings
- Multi-language support (English, Russian, German, Spanish, French)
- Custom fields and dynamic forms
- Excel export functionality
- Role-based permissions system
- Project management module
- File attachments support
- Field comments and history tracking

## 1.1.0 – 2025-11-21

### Added
- Selecting a color in an entity preview
- Preloaders for the Statistics and Reports sections
- Loaders in entity forms

### Changed
- Formatting headers in the Statistics section

## 1.2.0 – 2025-12-04

### Added
- User profile page with customizable appearance and image uploads
- Virtual scrolling for statistics tables (improved performance with large
  datasets)
- Sticky headers in statistics views
- Smart date range filtering based on user permissions
- Responsive sidebar navigation with mode switching

### Changed
- Refactored navigation structure for better consistency
- Improved time tracking interface and data loading
- Enhanced layout system with better Nextcloud integration

### Fixed
- Duplicate records when changing dynamic field types
- Issues with dynamic field deletion
- Sticky header behavior in statistics preview
- Various UI edge cases

## 1.3.0 – 2025-12-18

### Added
- Switching to the list of deleted employees in the employee table
- Possibility of restorement of deleted employees

### Changed
- Display the real names of deleted employees with a deletion mark instead of displaying their ID
- Improving the application codebase

## 1.4.0 – 2026-01-08

### Changed
- Handle missing permission in Roles model
- Refactor model names to remove trailing underscore