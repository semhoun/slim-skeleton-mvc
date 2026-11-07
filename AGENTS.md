# Repository Guidance

## Setup And Commands
- Use PHP 8.5 (`composer.json` requires `^8.5`; Rector targets PHP 8.5), not the README's PHP 8.4. Install with `composer install`; SQLite needs both the declared `sqlite3` extension and `pdo_sqlite` used by Doctrine.
- Run `composer start` for the development server at `http://localhost:8080`, with `public/index.php` as the router. There is no `./console serve` command.
- Initialize the SQLite database with `./console migrations:migrate`. Its path is fixed in `config/settings/database.php` to `var/database.sqlite`; database environment variables are not wired up.
- Generate Doctrine proxies with `./console app:generate-proxies`, not the README's `orm:generate-proxies`. Use `./console list` to verify available commands.
- Keep `var/` writable for the database, logs, templates, and generated caches. `vendor/`, `var/*`, and `composer.lock` are ignored; the lockfile is not tracked.
- The local, ignored `compose.yml` is host-specific (absolute bind mounts, Traefik, external `internal` network), not a portable development setup.

## Verification
- `composer test` runs PHPUnit 10 using `phpunit.xml`: `Unit` covers `tests/Services`; `Functional` covers `tests/Controller`.
- Focus a suite with `vendor/bin/phpunit --testsuite Unit`; focus a test with `vendor/bin/phpunit tests/Services/SettingsTest.php --filter testMethodName` (replace the method name).
- `tests/EnvHelperTest.php` is outside both configured suites. Run it explicitly with `vendor/bin/phpunit tests/EnvHelperTest.php` when changing environment helpers. Its `testEnvThrowsExceptionForMandatoryVar` currently fails: it expects `env()` to enforce a mandatory variable, which only `env_required()` does.
- Functional tests build the Slim app in-process through `tests/TestCaseBase.php`, without a running HTTP server. The not-found test logs a 404 stack trace even when the suite passes.
- Read-only quality checks: `composer insights-check` and `vendor/bin/rector process --dry-run`. Rector scans only `src/` and `config/`.
- `composer pre-commit` is a mutating script, not a test gate: it runs `dos2unix` first, then `composer insights-fix`, then `composer rector-fix`. It requires `dos2unix` and can change unrelated files.

## Wiring And Gotchas
- HTTP bootstrapping lives in `public/index.php`; CLI bootstrapping lives in `console`. Both use `Settings::load()` and PHP-DI definitions in `config/dependencies.php`.
- `Settings::load()` merges `config/settings/_base_.php` at the top level and each other `config/settings/*.php` under its filename stem. Access nested values with dot paths, e.g. `database.doctrine.entity_path`.
- `config/routes.php` automatically loads every `config/routes/*.php` closure before installing the catch-all 404 route. API routes hardcode `/api` in `config/routes/api.php`; changing `api_base_path` alone does not move them.
- Twig templates are in `tmpl/`; Doctrine uses attribute mapping from `src/Entity/`. Migrations are wired through `console` and `config/settings/database.php`, not a separate Doctrine CLI config.
- `src/helpers.php` is Composer-autoloaded. `env()` loads the root `.env` once per process, prefers `$_ENV` over `getenv()`, and converts boolean/null/empty strings. Use `env_required()` for mandatory variables; `env('KEY')` alone returns null when absent.
- `DEBUG_MODE` defaults to true and enables Tracy, which is a dev dependency. Set `DEBUG_MODE=false` when running without dev dependencies. `APP_ENV` does not select a separate settings file.
- With debug disabled, the HTTP entrypoint caches the DI container and routes under `var/cache/`. After dependency or route changes, use `./console cache:clear`; it also removes Doctrine proxies, so regenerate them with `./console app:generate-proxies` before non-debug use.
