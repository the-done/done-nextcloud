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

## 1.5.0 – 2026-01-22

### Added
- Added the ability to display dynamic tables as cards
- Highlighting a record when scrolling to a new report
- New "Add" button on the statistics page that opens a form for submitting a report

### Changed
- Improved the logic of the "Today" button on the statistics page
- The primary action in the employee table has been changed to a view action
- Now employees with the "Head" role can see only those employees to whom they are related within the project (where they are assigned as heads)

## 1.6.0 – 2026-03-19

### Added
- Now the first and last columns are fixed in dynamic tables
- Added compatibility with version 33 of Nextcloud

## 1.7.0 – 2026-04-02

### Added
- The Report module has been added.<br>It is available to users assigned to the Officer role (all reports are available to them) or Head role.<br>Head role users can only access the project report, and only for those projects where they are assigned as the Head role.

## 1.8.0 – 2026-06-11

### Changed
- Improving the application codebase

## 1.9.0 – 2026-09-01

### Added
- Demo mode for the paid modules: Agreement, Finances, Teams and Vacations now
  appear in the navigation with realistic read-only sample data, so their
  features can be explored before purchase. Demo modules are marked with a
  "pro" badge.
- A setting to hide the demo modules.
- Pro version settings page (admin only): activate the paid modules with a
  license key and manage the license from a single screen.
- License details panel showing the license status, plan, masked key, update
  window, installed version and installed modules.
- License-server reachability notice and a link to the license service.
- Update checks backed by a daily background heartbeat that also keeps the
  license validated.

### Changed
- License activation and module installation are now separate actions:
  activating only validates the key, while installing (or reinstalling) the
  modules is a distinct step. The screen warns when the license is active but
  the modules are not installed.
- Installed paid modules keep working when the license server is unreachable or
  the license is revoked — nothing is disabled automatically.
- Read-only enforcement in demo mode: create, edit, delete controls, settings
  pages and export are hidden, and a warning is shown when a demo write is
  attempted.

### Fixed
- Demo entity cards (such as the team preview) no longer produce 404s and now
  render as read-only.
- Localized the demo table column titles.
- The Pro version screen now survives a page reload.
- The installed pro-package version is reported correctly for update checks.