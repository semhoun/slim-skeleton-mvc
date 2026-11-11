# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.2.1] - 2026-11-11

### Changed
- Updated `semhoun/slim-session` to 1.0.1. Successful authentication now starts a fresh session, discarding previous data and flash messages and resetting its lifetime instead of only rotating credentials.
- Session JWTs become eligible for renewal halfway through their actual lifetime, including tokens shortened by the absolute session deadline, instead of using a fixed ten-minute renewal window.

### Security
- Adopted the library's safeguards against issuing new credentials after failed session creation or replacement. Reauthentication revokes the previous identity; a failed replacement does not restore it or automatically replay the operation.

## [4.2.0] - 2026-11-09

### Added
- Opt-in AJAX/API profiling with slim-tracy 1.2, including a request history, correlated response timings, and a dedicated interface at `/_tracy/api`. Enable it with both `DEBUG_MODE=true` and `TRACY_API_PROFILER=true`.
- Administrative account creation with `./console app:user:create username`, using a hidden password prompt and confirmation.
- An explicit opt-out for Origin validation using an empty `SESSION_ORIGIN`; CSRF protection remains mandatory. Changing between this mode and a configured origin requires users to sign in again.
- `TRUSTED_PROXIES` configuration to restrict forwarded headers to explicitly trusted proxy IP addresses.
- Configurable API base paths, route caching, and temporary storage paths.
- Cache maintenance through `cache:clear` and `cache:init`, and Doctrine proxy generation through `app:generate-proxies`.
- Environment configuration through vlucas/phpdotenv.
- Project guidance for applications built from this skeleton.

### Changed
- PHP 8.5 or later is now required.
- Updated Slim to the 4.15 series and integrated Doctrine ORM 3.5 with DBAL 4.4.
- Upgraded slim-tracy to 1.2. Development debugging currently requires PHP 8.5 and the `mbstring` extension; slim-tracy does not yet support PHP 8.6.
- Tracy now observes responses after Slim's error handling, so handled JSON errors can be profiled without changing their status or body.
- Replaced `odan/session` with `semhoun/slim-session` 1.0 for server-backed sessions. Existing users must sign in again after upgrading.
- Session setup now requires a private `var/sessions` directory with mode `0700`, a `SESSION_SIGNING_KEY` of at least 32 bytes, and an explicitly set `SESSION_ORIGIN`. See the README for deployment instructions.
- Opening the login form no longer creates a session; sessions are created only after successful authentication.
- Administration pages, database exports, and API writes now require an authenticated session. API reads remain public.
- Logout now requires POST; GET requests no longer log users out.
- API responses, including errors, always use JSON regardless of the `Accept` header.
- SQLite extension requirements now match Doctrine's PDO driver: `pdo` and `pdo_sqlite`.
- Updated setup and deployment documentation with the available console commands and production configuration.

### Fixed
- Login and logout now persist authentication changes and regenerate the session ID correctly.
- Session errors retain structured JSON API responses, including when using a custom API prefix or application base path.
- Invalid or expired session cookies are cleared and public browser pages reload instead of displaying an empty authentication error. Protected routes and mutations remain denied.
- Corrected route parameters for viewing and deleting posts, and return 404 for missing posts.
- API post creation no longer requires an unused `slug` field; invalid request field types return validation errors.
- Restored migration discovery while preserving existing migration identities, and fixed migration simulations on a fresh database.
- Routing errors are now handled consistently, preserving native 404 and 405 responses.
- Profiler routes work with PHP-DI controller injection and application base paths.
- Restored Doctrine proxy generation for production use.
- Cache clearing now removes hidden entries, continues past inaccessible entries, and reports permission failures instead of claiming success.
- JSON rendering preserves numeric strings and handles invalid UTF-8 in text and error messages.

### Removed
- The `SESSION_COOKIE_SECURE` option. Session cookies are always Secure; local HTTP development requires the browser's localhost exception, otherwise use local HTTPS.
- Native Tracy AJAX interception and its `X-Tracy-Ajax` header behavior, removed upstream in slim-tracy 1.2. Use the opt-in profiler and built-in fetch/XHR client instead; collected responses use `X-SlimTracy-Profile`.
- Global slim-tracy debugging shortcuts, replaced upstream by `SlimTracy\Debug` methods.

### Security
- Removed seeded administrative credentials and added a migration to remove existing accounts still using the historical default password. Accounts with customized passwords are preserved; the cleanup cannot be reversed.
- Historical default credentials are rejected even before the cleanup migration is applied.
- Fixed HTML injection in API previews and administrative tables.
- Internal exception details are logged instead of exposed in production responses.
- Debugging is disabled by default, and the `__Host-slim-session` cookie always uses Secure, HttpOnly, and SameSite=Lax.
- Authenticated form and API mutations require a session-bound CSRF token and, unless explicitly disabled, a matching Origin. Same-origin AJAX requests supply the CSRF token automatically.
- Signing in rotates existing session credentials, and signing out revokes the server-side session so previous cookies cannot be reused.
- Forwarded headers are ignored unless their source is a configured trusted proxy.
- Cache clearing no longer follows directory symlinks outside the cache tree.
- Private environment files are ignored by Git.
- Updated Slim, Twig, and Symfony dependencies to address known security advisories.
- API profiling is disabled by default, uses private bounded storage outside the web root, and defaults to same-origin access. Profiler endpoints are unauthenticated and share history across users: enable them only on a protected development network, as raw SQL may contain sensitive values. Hiding the Tracy bar does not disable profiling.

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
