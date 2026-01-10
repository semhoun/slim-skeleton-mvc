# Slim 4 MVC Skeleton

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/62644bc058af464eb2cfcf564c3500d6)](https://www.codacy.com/gh/semhoun/slim-skeleton-mvc/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=semhoun/slim-skeleton-mvc&amp;utm_campaign=Badge_Grade) [![Latest Stable Version](https://poser.pugx.org/semhoun/slim-skeleton-mvc/v/stable)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![Total Downloads](https://poser.pugx.org/semhoun/slim-skeleton-mvc/downloads)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![License](https://poser.pugx.org/semhoun/slim-skeleton-mvc/license)](https://packagist.org/packages/semhoun/slim-skeleton-mvc)

A modern web application skeleton based on **Slim 4**, using the **MVC** architecture.
This project is ideal for quickly starting the development of an API or a web application with a robust stack.

## 🚀 Features

* **Framework:** [Slim 4.15](http://www.slimframework.com/) (PSR-7, PSR-15, PSR-17)
* **PHP Version:** 8.4+ required
* **Dependency Injection Container:** [PHP-DI 7.0](http://php-di.org/) with autowiring
* **ORM:** [Doctrine ORM 3.5](https://github.com/doctrine/orm) with [DBAL 4.4](https://github.com/doctrine/dbal)
* **Database:** SQLite by default (supports MySQL, PostgreSQL via configuration)
* **Template Engine:** [Twig 3.4](https://twig.symfony.com/)
* **API Support:** RESTful API skeleton with JSON rendering
* **Logging:** [Monolog 3.9](https://github.com/Seldaek/monolog) with multiple handlers
* **Console:** [Symfony Console 6.4](https://github.com/symfony/console) with custom commands
* **Code Quality:** [PHP Insights 2.13](https://phpinsights.com/) & [Rector 2.2](https://getrector.com/)
* **Debugging:** [Tracy](https://tracy.nette.org/) integrated via [slim-tracy](https://github.com/semhoun/slim-tracy)
* **Sessions:** [Odan Session 6.4](https://github.com/odan/session) with secure storage
* **Migrations:** [Doctrine Migrations 3.9](https://github.com/doctrine/migrations)
* **Environment:** [vlucas/phpdotenv 5.6](https://github.com/vlucas/phpdotenv) for configuration (supports `.env` files, system environment variables, and mandatory variables using `env_required(['VAR1', 'VAR2'])`
* **Proxy Support:** Built-in proxy detection middleware

## 🛠️ Requirements

* PHP 8.4 or higher
* Composer 2.0 or higher
* SQLite extension (or MySQL/PostgreSQL for production)
* Required PHP extensions: json, pdo, pdo_sqlite

## 🛠️ Installation

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

2. By default, the application uses a **SQLite** database located in `var/database.sqlite`. For production, configure your database and other settings in the `.env` file or via **environment variables**.

3. Initialize the database:
```bash
./console migrations:migrate
```

4. (Optional) Generate Doctrine proxies:
```bash
./console orm:generate-proxies
```

### 3. Run the application

You can use the PHP built-in development server:
```bash
composer start
```

Or use the console command:
```bash
./console serve
```

The application will be accessible at [http://localhost:8080](http://localhost:8080).

### 4. Available Console Commands

```bash
# Clear application cache
./console cache:clear

# Initialize cache
./console cache:init

# Generate Doctrine proxies
./console orm:generate-proxies

# Run development server
./console serve

# List all available commands
./console list
```
## 🧪 Development Tools

Run unit tests:
```bash
composer test
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

Run pre-commit checks (code style + rector + dos2unix):
```bash
composer pre-commit
```


A `compose.yml` file is provided to facilitate deployment. Here is a configuration example:

```yaml
services:
  slim:
    image: semhoun/webserver
    ports:
      - "8080:80"
    volumes:
      - .:/www
    environment:
      - APP_ENV=dev
      - DEBUG_MODE=true
```

To launch the environment with Docker:

```bash
docker compose up -d
```

## 📝 Notes

* Ensure that the `var` folder has write permissions (for cache, logs, database)
* Default credentials: **admin** / **admin** (change in production!)
* The project includes an error rendering system (HTML/JSON) based on the `Accept` header
* API routes are prefixed with `/api` (configurable in `config/settings/_base_.php`)

## 📚 Documentation

* [Slim Framework Documentation](https://www.slimframework.com/docs/)
* [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
* [Twig Documentation](https://twig.symfony.com/doc/3.x/)
* [PHP-DI Documentation](https://php-di.org/doc/)

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## 🔄 Changelog

See the [CHANGELOG.md](CHANGELOG.md) file for version history and upcoming changes.
