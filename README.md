# Slim 4 MVC Skeleton

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/62644bc058af464eb2cfcf564c3500d6)](https://www.codacy.com/gh/semhoun/slim-skeleton-mvc/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=semhoun/slim-skeleton-mvc&amp;utm_campaign=Badge_Grade) [![Latest Stable Version](https://poser.pugx.org/semhoun/slim-skeleton-mvc/v/stable)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![Total Downloads](https://poser.pugx.org/semhoun/slim-skeleton-mvc/downloads)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![License](https://poser.pugx.org/semhoun/slim-skeleton-mvc/license)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/semhoun/slim-skeleton-mvc)

A modern web application skeleton based on **Slim 4**, using the **MVC** architecture.
This project is ideal for quickly starting the development of an API or a web application with a robust stack.

## Features

* **Framework:** [Slim 4.15](http://www.slimframework.com/) (PSR-7, PSR-15, PSR-17)
* **PHP Version:** 8.5+ required
* **Dependency Injection Container:** [PHP-DI 7.0](http://php-di.org/) with autowiring
* **ORM:** [Doctrine ORM 3.5](https://github.com/doctrine/orm) with [DBAL 4.4](https://github.com/doctrine/dbal)
* **Database:** SQLite, configured in `config/settings/database.php`
* **Template Engine:** [Twig 3.30](https://twig.symfony.com/)
* **API Support:** RESTful API skeleton with JSON rendering
* **Logging:** [Monolog 3.9](https://github.com/Seldaek/monolog) with multiple handlers
* **Console:** [Symfony Console 6.4](https://github.com/symfony/console) with custom commands
* **Code Quality:** [PHP Insights 2.13](https://phpinsights.com/) & [Rector 2.2](https://getrector.com/)
* **Debugging:** [Tracy](https://tracy.nette.org/) integrated via [slim-tracy](https://github.com/semhoun/slim-tracy)
* **Sessions:** [semhoun/slim-session 1.0](https://github.com/semhoun/slim-session) with `SessionContext`, server-side storage and mandatory CSRF protection
* **Migrations:** [Doctrine Migrations 3.9](https://github.com/doctrine/migrations)
* **Environment:** [vlucas/phpdotenv 5.6](https://github.com/vlucas/phpdotenv) for configuration (supports `.env` files, system environment variables, and mandatory variables using `env_required(['VAR1', 'VAR2'])`
* **Proxy Support:** Built-in proxy detection middleware

## Requirements

* PHP 8.5 or higher
* Composer 2.0 or higher
* Required PHP extensions: json, pdo, pdo_sqlite

## Installation

### 1. Create the project

Use Composer to install the project:

```bash
composer create-project semhoun/slim-skeleton-mvc [your-app]
```

### 2. Configuration

1. Copy the `.env.example` file to `.env`:
```bash
cp .env.example .env
```

2. The application uses a **SQLite** database located in `var/database.sqlite`. Database paths are configured in `config/settings/database.php`, not through database environment variables. `.env.example` explicitly enables development settings; never deploy these unchanged to production. Private `.env` files are ignored by Git.

3. Configure the required session values in `.env` or the process environment. `SESSION_SIGNING_KEY` must contain at least 32 bytes. Generate a private key locally and use the output as its value:
```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Set `SESSION_ORIGIN` explicitly to the canonical browser origin, including scheme and non-default port, without a path (for local development: `http://localhost:8080`). Use HTTPS outside localhost. The `__Host-slim-session` cookie is always Secure, HttpOnly and SameSite=Lax; `SESSION_COOKIE_SECURE` has been removed. Local HTTP development relies on the browser's Secure-cookie exception for localhost; use local HTTPS if unsupported.

To explicitly disable Origin validation, set `SESSION_ORIGIN=`. Whitespace-only values also disable it; omitting the variable remains an error. CSRF validation stays mandatory. This opt-out weakens cross-site request defenses; keep a canonical origin configured when possible. In this mode, the library uses the fixed technical origin `https://session.invalid` for configuration and the JWT issuer, never the request Host header. Switching between this mode and a configured origin changes the issuer and invalidates existing session tokens.

4. Before starting the application, manually provision the private session directory as the application account:
```bash
mkdir -p var/sessions && chmod 700 var/sessions
```

`FileSessionStore` uses `var/sessions` outside the public document root. It requires a local POSIX filesystem on a single host, with mode `0700` and ownership allowing the application account to access it. Do not share it across hosts or expose it through the web server. Repeat this provisioning for each deployment; application startup does not replace it.

5. Initialize the database:
```bash
./console migrations:migrate
```

6. Create an administrative account. The command prompts for a password and confirmation without displaying either. Passwords require at least 12 characters and at most 72 bytes. There are no default credentials.
```bash
./console app:user:create operator
```

7. Generate Doctrine proxies for non-debug use:
```bash
./console app:generate-proxies
```

### 3. Run the application

You can use the PHP built-in development server:
```bash
composer start
```

The application will be accessible at [http://localhost:8080](http://localhost:8080). Complete session configuration and directory provisioning above before launching.

### 4. Available Console Commands

```bash
# Clear application cache
./console cache:clear

# Initialize cache
./console cache:init

# Generate Doctrine proxies
./console app:generate-proxies

# Create an administrative account (interactive hidden password entry)
./console app:user:create operator

# List all available commands
./console list
```
## Development Tools

Run unit and functional tests, including environment helpers. Functional tests use an isolated SQLite in-memory database and `SessionContext` backed by `InMemorySessionStore`, not production session files:
```bash
composer test
```

Audit installed dependencies:
```bash
composer audit
```

Check code quality with PHP Insights:
```bash
composer insights-check
```

Apply automatic Rector fixes:
```bash
composer rector-fix
```

Fix code style issues:
```bash
composer insights-fix
```

Run automatic pre-commit fixes (mutates files; requires `dos2unix`):
```bash
composer pre-commit
```


## Authentication And API

All accounts provisioned by this skeleton have administrative access. There is no public registration or role hierarchy. Add explicit roles before introducing non-administrative accounts.

* `/admin` and its database exports require authentication.
* API GET requests are public and always return JSON, regardless of `Accept`.
* API POST and DELETE require an authenticated session cookie and a valid `X-CSRF-Token`. When `SESSION_ORIGIN` is nonempty, they also require a matching `Origin`.
* GET `/member/login` displays the login form without creating a session; a session is created only after successful authentication.
* POST `/member/login` accepts `uname`, `pswd` and the hidden `_csrf` field supplied by the form.
* Logout uses POST `/member/logout`. GET does not log out.
* API POST requests accept `content` (required non-empty string) and `title` (optional string or null). No `slug` is required.
* Preserve the session cookie when making API requests. The session ID changes on login and logout.

The application middleware exposes the Twig global `csrf_token` (`string|null`). POST forms, including login and logout, submit it as `_csrf`. The middleware converts that field to `X-CSRF-Token` only when the header is absent; an explicit header takes precedence. The session library always enforces CSRF checks for authenticated mutations and also validates Origin unless `SESSION_ORIGIN` is explicitly empty. Do not bypass enabled checks.

The shared Twig layout exposes the token through `<meta name="csrf-token">`. Its jQuery `ajaxSetup` adds `X-CSRF-Token` to same-origin mutations only, not GET requests or cross-origin requests. Custom JSON clients must obtain the token from a rendered page for the current session and send the token header, plus the canonical `Origin` when Origin validation is enabled. Browsers supply Origin for same-origin mutations automatically. Reload the page after authentication changes to obtain the current token.

Migrating from the previous session implementation invalidates existing sessions. All users must sign in again.

## Production

Serve only the `public/` directory over HTTPS. Configure these environment values explicitly:

```dotenv
DEBUG_MODE=false
TRUSTED_PROXIES=
```

Also set the mandatory `SESSION_SIGNING_KEY` and an explicit `SESSION_ORIGIN`, preferably the canonical HTTPS origin, using the configuration instructions above. Provision `var/sessions` with mode `0700` before launch. Keep the signing key private and stable across restarts; changing it invalidates existing sessions.

Debugging is disabled by default. `APP_ENV` does not select a separate configuration or override `DEBUG_MODE`. With debug enabled, Tracy is intended only for a private development environment and requires development dependencies.

Forwarded headers are ignored unless `TRUSTED_PROXIES` contains a comma-separated list of exact trusted proxy IP addresses. Configure the proxy to replace client-supplied forwarding headers. Do not list client addresses or use a wildcard. The `__Host-slim-session` cookie is always Secure, HttpOnly and SameSite=Lax. HTTPS is required outside localhost; there is no configuration to disable Secure.

For an existing deployment, back up the SQLite database before applying migrations. Migration `Skeleton\Version20261107152100` removes only accounts named `admin` whose password is still the old default. The historical default credentials are also refused by login before this migration runs. Accounts with customized passwords are preserved. The cleanup is irreversible; provision a new administrative account if needed.

After updating dependencies or routes:

```bash
composer install --no-dev --optimize-autoloader
./console migrations:migrate
./console cache:clear
./console app:generate-proxies
composer audit --no-dev
```

Run cache commands as the account owning the generated files. A permissions error makes `cache:clear` fail; fix ownership through your deployment process before serving updated templates. Doctrine entities must remain non-final while generated proxies are used.

`composer.lock` is ignored by this reusable skeleton. Application deployments should retain their generated lockfile and use `composer install` for reproducible releases. Run `composer update --with-all-dependencies` when intentionally updating dependencies, then test and audit the result. A local `compose.yml`, if present, is host-specific and is not a portable deployment configuration.

## Notes

* Ensure that the `var` folder has write permissions (for cache, logs, database)
* Internal error details are logged, not returned to production clients. API errors remain JSON; other routes negotiate HTML/JSON using `Accept`.
* API routes are prefixed with `/api` (configurable in `config/settings/_base_.php`)

## Documentation

* [Slim Framework Documentation](https://www.slimframework.com/docs/)
* [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
* [Twig Documentation](https://twig.symfony.com/doc/3.x/)
* [PHP-DI Documentation](https://php-di.org/doc/)

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## Changelog

See the [CHANGELOG.md](CHANGELOG.md) file for version history and upcoming changes.
