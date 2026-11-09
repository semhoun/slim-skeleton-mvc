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
- Never commit `.env`, credentials, database backups, generated caches, or `vendor/`. In a derived application, track `composer.lock` for reproducible deployments; adjust the inherited `.gitignore` accordingly.
- Use the application's documented deployment configuration. Do not assume a particular container runtime, reverse proxy, network, or host filesystem layout.

## Architecture
- HTTP bootstrapping lives in `public/index.php`; CLI bootstrapping lives in `console`. Both use `Settings::load()` and PHP-DI definitions in `config/dependencies.php`.
- `Settings::load()` merges `config/settings/_base_.php` at the top level and other `config/settings/*.php` files under their filename stem. Access nested values with dot paths, such as `database.doctrine.entity_path`.
- Add route definitions under `config/routes/`; `config/routes.php` loads each closure. Use named routes in templates and honor `api_base_path` for API endpoints.
- Register middleware in `config/middleware.php`. Slim executes middleware in reverse registration order: error handling must wrap routing, body parsing, and application middleware. Do not duplicate registration in the entrypoint or add a catch-all route that masks native 405 responses.
- Keep HTTP handling in `src/Controller/`, reusable application logic in `src/Services/`, persistence queries in `src/Repository/`, Doctrine entities in `src/Entity/`, and Twig templates in `tmpl/`.
- Add schema changes through migrations. The namespace in `migrations_paths` must match the migration classes. Preserve the identities of executed migrations; changing their namespace can make Doctrine treat them as new migrations.

## Security
- Enforce authentication and authorization on the server, not just in navigation or templates. The initial account model grants administrative access to every provisioned account; add explicit roles and permissions before introducing non-administrative users.
- Logout is POST-only, and authentication changes regenerate the session ID.
- Validate request types and required fields before calling typed application code. Return appropriate 4xx responses for invalid input and missing resources.
- Return JSON with an explicit content type for API responses. Preserve Twig autoescaping and escape data inserted into HTML or client-side tables; JSON encoding alone does not make data safe for HTML.
- Keep internal exception messages and traces in logs, not production responses. Keep debugging disabled in production; enabling `DEBUG_MODE` loads the optional Tracy development dependency.
- Use Secure, HttpOnly, SameSite session cookies. Disable `SESSION_COOKIE_SECURE` only for local HTTP development. Trust forwarding headers only from exact proxy IPs configured in `TRUSTED_PROXIES`; an empty list ignores those headers.
- Use `env_required()` for mandatory configuration. `env()` loads `.env` once per process, prefers `$_ENV` over `getenv()`, and converts boolean/null/empty values. `APP_ENV` does not automatically select a settings file.

## Verification And Maintenance
- Run `composer test` after changes. `phpunit.xml` defines unit tests in `tests/Services` and `tests/EnvHelperTest.php`, and functional tests in `tests/Controller`; extend these suites as the application grows.
- Functional tests use `tests/TestCaseBase.php` with the real middleware stack, an isolated SQLite in-memory database, and MemorySession. Do not use the application's live database for tests.
- Run focused checks with `vendor/bin/phpunit --testsuite Unit` or `vendor/bin/phpunit path/to/Test.php --filter testMethodName`.
- Run `composer audit`, `composer insights-check`, and `vendor/bin/rector process --dry-run`. Review dependency updates and add regression tests for security and behavior changes.
- `composer pre-commit` modifies files through `dos2unix`, Insights, and Rector. Review its diff and avoid unrelated rewrites.
- After dependency or route changes, run `./console cache:clear` with an account permitted to remove the generated files, then `./console app:generate-proxies`. Non-debug HTTP bootstrapping caches the container and routes under `var/cache/`.
