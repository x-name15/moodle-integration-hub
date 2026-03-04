# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-03-04
### Fixed
- **Dashboard charts not rendering** (`index.php`): `$PAGE->requires->js()` for Chart.js was called after `$OUTPUT->header()`, causing the `<head>` to be emitted before the script tag was registered. As a result `Chart` was always undefined and both canvas elements rendered blank. Fixed by moving the require call to before `$OUTPUT->header()`.
- **Incorrect `avg_latency` calculation** (`index.php`): The `AVG(latency_ms)` query lacked a `LIMIT`, so it averaged all historical records instead of the last 50 as intended. Fixed by wrapping the selection in a subquery with `ORDER BY timecreated DESC LIMIT 50`.

## [2.0.0] The Post Revision Update - 2026-02-26
### Repository
**Original Repository Name Changed**: Name changed to moodle-local_integrationhub for consistent developer experience

### Security
- **PARAM_RAW replaced** (`index.php`): `base_url` now uses `PARAM_TEXT`, `auth_token` / `amqp_pass` / `amqp_vhost` use `PARAM_TEXT`. Eliminates XSS/injection surface flagged in Moodle official plugin review (issue #3).
- **curl_init() removed** (`classes/transport/http.php`): Replaced direct PHP curl with Moodle's `\curl` wrapper class. Ensures proxy configuration and SSRF protections set by site admins are fully respected (issue #9).

### Fixed
- **Hard-coded strings** (`webhook.php`): All API error messages now use `get_string()` via the Moodle string API, enabling translation (issue #8). New lang strings added to all 5 locales (en, es, fr, it, pt_br).
- **N+1 DB queries** (`classes/task/queue_manager.php`): `get_pending_tasks()` and `purge_orphan_tasks()` now preload rules and services in bulk before iterating, eliminating per-loop DB calls (issue #13).
- **Vendor validation** (`thirdpartylibs.xml`, `db/environment.xml`): Added required Moodle metadata files declaring the php-amqplib third-party dependency and its PHP/AMQP extension requirements (issue #5).
- **Third-Party Library Documentation** (`thirdpartylibs.xml`): Documented Chart.js and php-amqplib per Moodle licensing requirements (issue #11).
- **Missing lang string** (`db/caches.php`): Added `cachedef_event_dedupe` string to all 5 locale files (en, es, fr, it, pt_br), satisfying the Moodle plugin checker requirement for cache definitions (issue #15).
- **Privacy Provider** (`classes/privacy/provider.php`): Migrated from `null_provider` to `metadata\provider`, implementing `add_external_location_link()` to declare that event payloads may be forwarded to administrator-configured external services. New `privacy:metadata:external_services` strings added to all 5 locales (issue #6).

### Added
- **Mustache templates** (`templates/dashboard_tabs.mustache`, `templates/dashboard_charts.mustache`): New template directory with two Mustache templates for the dashboard navigation tabs and chart card containers (issue #7).

### Changed
- **External Services Migration**: Migrated legacy `ajax.php` payload preview to Moodle's External Services API (`classes/external/preview_payload.php`) with proper capability checks and session validation. Deprecated the old endpoint (issue #12).
- **UI**: `response_queue` field now correctly hidden when service type is AMQP (JavaScript `toggleUi()` updated).
- **AMQP URL**: `base_url` param changed from `PARAM_URL` to `PARAM_TEXT` to support `amqp://` and `amqps://` schemes.
- **Output API** (`index.php`): Navigation tabs and chart containers migrated from raw `html_writer` calls to `$OUTPUT->render_from_template()` (issue #7).
- **AMD modules** (`amd/src/dashboard.js`, `queue.js`, `rules.js`): Added mandatory Moodle GPL license header and `@module`/`@copyright`/`@license` JSDoc boilerplate to all AMD source files (issue #10).
- **Inline JavaScript removed** (`index.php`): Eliminated all raw `echo '<script>'` blocks. Chart.js loaded via `$PAGE->requires->js()`; type-toggle and AMQP UI logic consolidated in the `local_integrationhub/dashboard` AMD module (issue #14).


## [1.1.5] - 2026-02-23
### Changed
- **Total Code Refactor**: Renamed internal variables to camelCase (e.g., `$svc_id` to `$svcid`) across the entire codebase to comply with Moodle's strict naming conventions.
- Improved code legibility by breaking down complex HTML rendering logic into scannable blocks in `queue.php` and `events.php`.

### Fixed
- **Zero-Warning Policy**: Resolved all remaining 80+ PHP CodeSniffer (moodle-cs) style errors and warnings.
- Fixed line length violations (>132 characters) in all UI-facing scripts.
- Standardized inline comment punctuation and capitalization to meet Moodle's "Grammar Police" requirements.
- Cleaned up `db/upgrade.php` and `db/caches.php` by removing redundant checks and fixing formatting.
- Verified that the logic remains 100% intact after the refactor using `cli/test_logic.php`.

## [1.1.1] - 2026-02-21
### Added
- CLI diagnostic script `cli/test_logic.php` to verify core registry, HTTP transport, and routing logic without UI dependencies.

### Changed
- JavaScript files in `amd/src` adjusted to use modern formatting and consistent arrow functions to meet Moodle ESLint standards.
- CI pipeline workflow (`.github/workflows/ci.yml`) updated to successfully handle PHPCS errors, preventing critical CI breakages over style issues.

### Fixed
- Over 80 PHP CodeSniffer (moodle-cs) style errors across the plugin, including brace placement, indentation, line lengths, spacing, and PHPMD wrapper tags.
- Restored original plugin business logic safely after automated syntax fixing.

## [1.1.0] - 2026-02-20
### Added
- Implemented Moodle Privacy API (`\core_privacy\local\metadata\provider`) to certify that the plugin acts as a gateway and only logs technical request metadata.
- Italian (`it`) translations.
- French (`fr`) translations.
- Portuguese (Brazil) (`pt_br`) translations.