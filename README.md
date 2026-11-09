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
* **Sessions:** [Odan Session 6.4](https://github.com/odan/session) with secure storage
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

3. Initialize the database:
```bash
./console migrations:migrate
```

4. Create an administrative account. The command prompts for a password and confirmation without displaying either. Passwords require at least 12 characters and at most 72 bytes. There are no default credentials.
```bash
./console app:user:create operator
```

5. Generate Doctrine proxies for non-debug use:
```bash
./console app:generate-proxies
```

### 3. Run the application

You can use the PHP built-in development server:
```bash
composer start
```

The application will be accessible at [http://localhost:8080](http://localhost:8080).

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

Run unit and functional tests, including environment helpers. Functional tests use an isolated in-memory database and session:
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
* API POST and DELETE require an authenticated session cookie.
* GET `/member/login` displays the login form and establishes a session.
* POST `/member/login` accepts `uname` and `pswd`.
* Logout uses POST `/member/logout`. GET does not log out.
* API POST requests accept `content` (required non-empty string) and `title` (optional string or null). No `slug` is required.
* Preserve the session cookie when making API requests. The session ID changes on login and logout.

## Production

Serve only the `public/` directory over HTTPS. Configure these environment values explicitly:

```dotenv
DEBUG_MODE=false
SESSION_COOKIE_SECURE=true
TRUSTED_PROXIES=
```

Debugging is disabled by default. `APP_ENV` does not select a separate configuration or override `DEBUG_MODE`. With debug enabled, Tracy is intended only for a private development environment and requires development dependencies.

Forwarded headers are ignored unless `TRUSTED_PROXIES` contains a comma-separated list of exact trusted proxy IP addresses. Configure the proxy to replace client-supplied forwarding headers. Do not list client addresses or use a wildcard. Session cookies use HttpOnly, SameSite=Lax and strict session IDs. Disable Secure cookies only for local HTTP development.

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
