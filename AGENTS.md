# Project Guidance

These instructions apply to the application built from this skeleton. Update them as the application's architecture, domain, commands, and deployment requirements evolve. Treat the supplied blog and CRUD endpoints as examples to adapt or replace, not as required business features.

## Setup And Commands
- Use the PHP version and extensions declared in `composer.json`, and keep the PHP target in `rector.php` aligned. Install dependencies with `composer install`.
- Run `composer start` for local development at `http://localhost:8080`. Configure production servers with `public/` as the document root.
- Configure persistence in `config/settings/database.php`. The initial configuration uses SQLite at `var/database.sqlite`; database environment variables take effect only when explicitly wired into the configuration.
- Apply migrations with `./console migrations:migrate`. Back up existing application data before deploying migrations that change or remove it.
- Provision administrative accounts with `./console app:user:create username`, which prompts for a hidden password. Do not introduce seeded credentials.
- Generate Doctrine proxies with `./console app:generate-proxies` before non-debug use. Keep entities non-final while using generated proxies.
- Keep `var/` writable by the application and maintenance accounts, with shared permissions where necessary. Do not solve permission errors by granting world-write access.
- Before launch, manually provision `FileSessionStore` storage as the application account: `mkdir -p var/sessions && chmod 700 var/sessions`. Keep this directory private, outside `public/`, owned by the application account, on a local POSIX filesystem on a single host. Do not share session files across hosts or relax mode `0700` for shared maintenance access.
- Require `SESSION_SIGNING_KEY` (at least 32 bytes; generate with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`) and an explicitly set `SESSION_ORIGIN`. Prefer a canonical origin (scheme, host and non-default port, no path). An explicitly empty or whitespace-only value disables Origin validation, but not CSRF; an absent variable remains an error. This mode uses `https://session.invalid` as the technical origin and JWT issuer, never the request Host. Switching modes changes the issuer and invalidates existing tokens. Keep the signing key private and stable across restarts. For local development use `http://localhost:8080`; use HTTPS outside localhost.
- Never commit `.env`, credentials, database backups, generated caches, or `vendor/`. In a derived application, track `composer.lock` for reproducible deployments; adjust the inherited `.gitignore` accordingly.
- Use the application's documented deployment configuration. Do not assume a particular container runtime, reverse proxy, network, or host filesystem layout.

## Architecture
- HTTP bootstrapping lives in `public/index.php`; CLI bootstrapping lives in `console`. Both use `Settings::load()` and PHP-DI definitions in `config/dependencies.php`.
- `Settings::load()` merges `config/settings/_base_.php` at the top level and other `config/settings/*.php` files under their filename stem. Access nested values with dot paths, such as `database.doctrine.entity_path`.
- Add route definitions under `config/routes/`; `config/routes.php` loads each closure. Use named routes in templates and honor `api_base_path` for API endpoints.
- Register middleware in `config/middleware.php`. Slim executes middleware in reverse registration order: error handling must wrap routing, body parsing, and application middleware. In debug mode, register Tracy last so it observes Slim-handled error responses. Do not duplicate registration in the entrypoint or add a catch-all route that masks native 405 responses.
- Keep HTTP handling in `src/Controller/`, reusable application logic in `src/Services/`, persistence queries in `src/Repository/`, Doctrine entities in `src/Entity/`, and Twig templates in `tmpl/`.
- Add schema changes through migrations. The namespace in `migrations_paths` must match the migration classes. Preserve the identities of executed migrations; changing their namespace can make Doctrine treat them as new migrations.

## Security
- Enforce authentication and authorization on the server, not just in navigation or templates. The initial account model grants administrative access to every provisioned account; add explicit roles and permissions before introducing non-administrative users.
- Logout is POST-only, and authentication changes regenerate the session ID.
- Use `semhoun/slim-session` 1.0.1+ and `SessionContext` for sessions. Successful authentication calls `startNew()` on the middleware-owned context, revoking the old identity and discarding old data/flash while resetting deadlines; do not replace the request context or use `rotate()` for a new authentication. Replacement is not atomic and failed operations must not be automatically replayed. Keep the library's default JWT renewal at half the actual token lifetime. Migrating from the previous session implementation invalidates existing sessions; users must sign in again.
- The library enforces mandatory CSRF for authenticated mutations; Origin validation is also enabled unless `SESSION_ORIGIN` is explicitly empty. Application middleware exposes Twig global `csrf_token` (`string|null`) and converts form `_csrf` to `X-CSRF-Token` only if that header is absent. Include hidden `_csrf` in every POST form, including login and logout. The shared layout's `csrf-token` meta tag and jQuery `ajaxSetup` supply the header for same-origin AJAX mutations only. Custom clients must send the token for the current session and, when enabled, an `Origin` matching `SESSION_ORIGIN`; never bypass enabled library checks.
- Validate request types and required fields before calling typed application code. Return appropriate 4xx responses for invalid input and missing resources.
- Return JSON with an explicit content type for API responses. Preserve Twig autoescaping and escape data inserted into HTML or client-side tables; JSON encoding alone does not make data safe for HTML.
- Keep internal exception messages and traces in logs, not production responses. Keep debugging disabled in production; enabling `DEBUG_MODE` loads the optional Tracy development dependency.
- With `semhoun/slim-tracy` 1.2, AJAX profiling requires both `DEBUG_MODE=true` and explicit `TRACY_API_PROFILER=true`. Its routes at `/_tracy/api` are unauthenticated and profiles are shared across users; enable only on a protected development network. Raw SQL can contain sensitive values. Keep profile storage under private `var/tmp/api-profiles`, outside `public/`, and same-origin defaults. The built-in Tracy assets instrument fetch/XHR; do not restore the removed native AJAX interception or bypass session/CSRF checks.
- The `__Host-slim-session` cookie is always Secure, HttpOnly and SameSite=Lax. `SESSION_COOKIE_SECURE` has been removed; never disable Secure. HTTPS is required outside localhost; local HTTP requires the browser's localhost Secure-cookie exception, otherwise use local HTTPS. Trust forwarding headers only from exact proxy IPs configured in `TRUSTED_PROXIES`; an empty list ignores those headers.
- Use `env_required()` for mandatory configuration. `env()` loads `.env` once per process, prefers `$_ENV` over `getenv()`, and converts boolean/null/empty values. `APP_ENV` does not automatically select a settings file.

## Verification And Maintenance
- Run `composer test` after changes. `phpunit.xml` defines unit tests in `tests/Services` and `tests/EnvHelperTest.php`, and functional tests in `tests/Controller`; extend these suites as the application grows.
- Functional tests use `tests/TestCaseBase.php` with the real middleware stack, an isolated SQLite in-memory database, and `SessionContext` backed by `InMemorySessionStore`. Do not use the application's live database or session files for tests.
- Run focused checks with `vendor/bin/phpunit --testsuite Unit` or `vendor/bin/phpunit path/to/Test.php --filter testMethodName`.
- Run `composer audit`, `composer insights-check`, and `vendor/bin/rector process --dry-run`. Review dependency updates and add regression tests for security and behavior changes.
- `composer pre-commit` modifies files through `dos2unix`, Insights, and Rector. Review its diff and avoid unrelated rewrites.
- After dependency or route changes, run `./console cache:clear` with an account permitted to remove the generated files, then `./console app:generate-proxies`. Non-debug HTTP bootstrapping caches the container and routes under `var/cache/`.
