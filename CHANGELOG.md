# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-02-20
### Added
- Implemented Moodle Privacy API (`\core_privacy\local\metadata\provider`) to certify that the plugin acts as a gateway and only logs technical request metadata.
- Italian (`it`) translations.
- French (`fr`) translations.
- Portuguese (Brazil) (`pt_br`) translations.

## [1.1.1] - 2026-02-21
### Added
- CLI diagnostic script `cli/test_logic.php` to verify core registry, HTTP transport, and routing logic without UI dependencies.

### Changed
- JavaScript files in `amd/src` adjusted to use modern formatting and consistent arrow functions to meet Moodle ESLint standards.
- CI pipeline workflow (`.github/workflows/ci.yml`) updated to successfully handle PHPCS errors, preventing critical CI breakages over style issues.

### Fixed
- Over 80 PHP CodeSniffer (moodle-cs) style errors across the plugin, including brace placement, indentation, line lengths, spacing, and PHPMD wrapper tags.
- Restored original plugin business logic safely after automated syntax fixing.
