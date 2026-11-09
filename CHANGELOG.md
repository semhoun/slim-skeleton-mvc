# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Administrative account creation with `./console app:user:create username`, using a hidden password prompt and confirmation.
- `SESSION_COOKIE_SECURE` configuration for local HTTP development and HTTPS deployments.
- `TRUSTED_PROXIES` configuration to restrict forwarded headers to explicitly trusted proxy IP addresses.
- Project guidance for applications built from this skeleton.

### Changed
- PHP 8.5 or later is now required.
- Administration pages, database exports, and API writes now require an authenticated session. API reads remain public.
- Logout now requires POST; GET requests no longer log users out.
- API responses, including errors, always use JSON regardless of the `Accept` header.
- SQLite extension requirements now match Doctrine's PDO driver: `pdo` and `pdo_sqlite`.
- Updated setup and deployment documentation with the available console commands and production configuration.

### Fixed
- Login and logout now persist authentication changes and regenerate the session ID correctly.
- Corrected route parameters for viewing and deleting posts, and return 404 for missing posts.
- API post creation no longer requires an unused `slug` field; invalid request field types return validation errors.
- Restored migration discovery while preserving existing migration identities, and fixed migration simulations on a fresh database.
- Routing errors are now handled consistently, preserving native 404 and 405 responses.
- Restored Doctrine proxy generation for production use.
- Cache clearing now removes hidden entries, continues past inaccessible entries, and reports permission failures instead of claiming success.
- JSON rendering preserves numeric strings and handles invalid UTF-8 in text and error messages.

### Security
- Removed seeded administrative credentials and added a migration to remove existing accounts still using the historical default password. Accounts with customized passwords are preserved; the cleanup cannot be reversed.
- Historical default credentials are rejected even before the cleanup migration is applied.
- Fixed HTML injection in API previews and administrative tables.
- Internal exception details are logged instead of exposed in production responses.
- Debugging is disabled by default, and session cookies use HttpOnly, SameSite=Lax, and Secure outside development mode.
- Forwarded headers are ignored unless their source is a configured trusted proxy.
- Cache clearing no longer follows directory symlinks outside the cache tree.
- Private environment files are ignored by Git.
- Updated Slim, Twig, and Symfony dependencies to address known security advisories.

## [4.2.0] - 2026-01-10
### Added
- PHP 8.4 support
- Doctrine ORM 3.5 and DBAL 4.4 integration
- API base path configuration in settings
- Temporary path configuration for file operations
- Route caching configuration
- New console commands: `cache:clear`, `cache:init`, `orm:generate-proxies`, `serve`
- Environment variable support via vlucas/phpdotenv
- Proxy detection middleware
- Enhanced Docker configuration with MySQL support
- Comprehensive documentation in README

### Changed
- Upgraded to Slim 4.15
- Updated all dependencies to latest stable versions
- Improved error handling and rendering system
- Enhanced configuration system with environment variables
- Modernized Docker setup with proper volume management
- Improved code quality tools configuration

### Fixed
- Various bug fixes and stability improvements

## [4.1.0] - 2024-09-01
### Added
- Full use of Dependency Injection (DI) throughout the application
- API skeleton with JSON rendering support
- Bootstrap 5 migration for frontend components

### Changed
- Upgraded to Doctrine ORM 3
- Improved DI container configuration

## [4.0.5] - 2023-01-23
### Added
- Tracy debugger integration for development
- Changelog file
- Custom slim-tracy integration

## [4.0.4] - 2022-08-17
### Fixed
- Fixed require/include statements
- Resolved issue #5 with Doctrine upgrade
- Added example of custom CSS (#4)

### Changed
- Updated Codacy badge in README

## [4.0.3] - 2021-01-06
### Fixed
- Code style: replaced tabs with spaces
- Added proxy detection for environments like Traefik

## [4.0.2] - 2020-01-19
### Fixed
- Code style: replaced spaces with tabs
- Various stability improvements

### Added
- Base path configuration

## [4.0.1] - 2019-10-05
### Changed
- Updated Twig view component
- Removed unnecessary factories

### Fixed
- README documentation fixes

## [4.0.0] - 2019-09-15
### Added
- Codacy badge to README (#1)
- Bootstrap template integration

### Changed
- Upgraded to Slim Framework 4
- Updated README documentation

## [3.0.0] - 2019-08-21
### Added
- JWT authentication support
- Directory structure refactoring
