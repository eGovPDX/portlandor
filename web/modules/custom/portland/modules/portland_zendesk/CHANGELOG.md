# Changelog

## [Unreleased]
### Changed
- Updated dependencies for Drupal 9


## [1.1.0] - 2021-01-05
### Added
- Implemented new Name Utility class to polyfill expected name values

### Fixed
- Fixed error when setting requester_name to full name on the webform
- Fixed issue attaching all uploaded files, regardless of the file field limit


## [1.0.0] - 2019-07-02
### Added
- Field reference for custom ticket fields
- New helper class Utility to separate helper functions for sanity and maintenance
- Launched full release

### Changed
- Deprecated helper functions on the ZendeskHandler class, to be removed in the next minor version
- Replaced old helper methods with Utility helper methods


## [0.3.0] - 2019-07-02
### Changed
- Updated install instructions after registering with Packagist

## [0.2.1] - 2019-06-27
### Added
- Means to update Drupal webform submission with Zendesk Ticket ID, if field is present on form
- Allow for specifying any hidden field on the form as the Zendesk Ticket ID Field to be updated

### Changed
- Updated the README file with instructions for configuring storage of the submission's Zendesk Ticket ID.

### Fixed
- Configuration menu link now appears in admin menu


## [0.2.0] - 2019-06-25
### Added
- New helper function for formatting names from name field

### Changed
- Split Requester field into separate fields for name and email fields
- Allow for setting name value from possible name fields
- Updated comments with more descriptions
- Update custom field description and text
- Retrieve subdomain setting for use in link
- Add placeholder value to custom fields field

### Fixed
- Changed YAML placeholder values to use single quotations
- Only display custom fields when present
- Removed second occurence of "clean up tags" block


## [0.1.0] - 2019-05-28
### Added
- Optionally set an assignee for the Webform Handler

### Changed
- Made project description more accurate
- Updated README file with documentation

### Fixed
- Webform submissions no longer create multiple tickets


## [0.0.3] - 2019-05-22
### Added
- Parsing for CC email addresses
- Now uploads webform file attachments to Zendesk ticket

### Changed
- Updated the settings form fields with field descriptions


## [0.0.2] - 2019-05-21
### Added
- Formatting and conditional helper functions
- Token manager for placeholder parsing
- Handler settings summary for display/listing page
- Link webform submission to Zendesk ticket by ID

### Changed
- Redefine default configuration fields
- Moved Zendesk new ticket call to be triggered on SaveForm call for new form submissions only


## [0.0.1] - 2019-05-19
### Added
- This CHANGELOG file to document changes in the codebase.
- This initial code base


[Unreleased]: https://github.com/strakers/zendesk-drupal-webform/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/strakers/zendesk-drupal-webform/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.3.0...v1.0.0
[0.3.0]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.0.3...v0.1.0
[0.0.3]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.0.2...v0.0.3
[0.0.2]: https://github.com/strakers/zendesk-drupal-webform/compare/v0.0.1...v0.0.2
[0.0.1]: https://github.com/strakers/zendesk-drupal-webform/releases/tag/v0.0.1