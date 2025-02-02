# Changelog

## 2.0.2 - 2025-01-10

### Added
- Add support for streams on delete and restore actions. (thanks @FabianRutishauser).

## 2.0.1 - 2024-09-07

### Added
- Add configurable backup option to review step before importing.
- Add Craft Teams support for permissions.

### Changed
- Update English translations.
- Update Product element handling for Commerce 5.x.
- Improve error messaging.

### Fixed
- Fix an error in Safari when viewing the export form interface.
- Fix Craft 5 visual issues.
- Fix a Craft 5 compatibility error.
- Fix an error when importing Matrix fields.
- Fix error handling for Entries where the section/entry type/author could not be found due to missing project config settings (these objects not - on the destination install).

## 2.0.0 - 2024-05-18

### Added
- Add support for Hyper fields

### Changed
- Now requires PHP `8.2.0+`.
- Now requires Craft `5.0.0+`.

### Fixed
- Fix some fields like Hyper and Vizy not showing in preview correctly.
- Fix display of importing deleted elements.
- Fix deleted/restored elements not exporting correctly.
- Fix an error when importing users and permissions.
- Fix some errors when importing assets where folders or file cannot be found.
- Fix an error when trying to preview an element with no parent.
- Fix an error for non-English sites.

## 1.0.6 - 2025-02-02

### Fixed
- Update error messages from ajax responses.

## 1.0.5 - 2024-09-07

### Added
- Add configurable backup option to review step before importing.

### Changed
- Update English translations.

### Fixed
- Fix an error in Safari when viewing the export form interface.

## 1.0.4 - 2024-03-18

### Added
- Add support for Hyper fields

### Fixed
- Fix some fields like Hyper and Vizy not showing in preview correctly.
- Fix display of importing deleted elements.
- Fix deleted/restored elements not exporting correctly.
- Fix an error when importing users and permissions.
- Fix some errors when importing assets where folders or file cannot be found.
- Fix an error when trying to preview an element with no parent.
- Fix an error for non-English sites.

## 1.0.3 - 2024-03-04

### Fixed
- Fix order of operations when uninstalling the plugin.

## 1.0.2 - 2023-12-08

### Added
- Add support for Event plugin Events/Tickets.

### Fixed
- Fix Neo field exports.
- Fix export dates not working correctly for non-US-based locales.

## 1.0.1 - 2023-11-05

### Fixed
- Fix an error installing on Postgres installs.
- Fix disabled dropdown values in preview, due to selectize.

## 1.0.0 - 2023-09-19

### Added
- Initial release
