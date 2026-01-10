# Slim 4 MVC Skeleton

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/62644bc058af464eb2cfcf564c3500d6)](https://www.codacy.com/gh/semhoun/slim-skeleton-mvc/dashboard?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=semhoun/slim-skeleton-mvc&amp;utm_campaign=Badge_Grade) [![Latest Stable Version](https://poser.pugx.org/semhoun/slim-skeleton-mvc/v/stable)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![Total Downloads](https://poser.pugx.org/semhoun/slim-skeleton-mvc/downloads)](https://packagist.org/packages/semhoun/slim-skeleton-mvc) [![License](https://poser.pugx.org/semhoun/slim-skeleton-mvc/license)](https://packagist.org/packages/semhoun/slim-skeleton-mvc)

A modern web application skeleton based on **Slim 4**, using the **MVC** architecture.
This project is ideal for quickly starting the development of an API or a web application with a robust stack.

## 🚀 Features

* **Framework:** [Slim 4](http://www.slimframework.com/)
* **Dependency Injection Container:** [PHP-DI](http://php-di.org/)
* **ORM:** [Doctrine 3](https://github.com/doctrine/orm) with SQLite by default
* **Template Engine:** [Twig 3](https://twig.symfony.com/)
* **Logging:** [Monolog](https://github.com/Seldaek/monolog)
* **Console:** [Symfony Console](https://github.com/symfony/console)
* **Code Quality:** [PHP Insights](https://phpinsights.com/) & [Rector](https://getrector.com/)
* **Debugging:** [Tracy](https://tracy.nette.org/) integrated via [slim-tracy](https://github.com/semhoun/slim-tracy)
* **Sessions:** [Odan Session](https://github.com/odan/session)
* **Migrations:** [Doctrine Migrations](https://github.com/doctrine/migrations)

## 🛠️ Installation

### 1. Create the project
Use Composer to install the project:
```bash
composer create-project semhoun/slim-skeleton-mvc [your-app]
```

### 2. Configuration
By default, the application uses a **SQLite** database located in `var/database.sqlite`.

Initialize the database:
```bash
./console migrations:migrate
```

### 3. Run the application
You can use the PHP built-in development server:
```bash
composer start
```
The application will be accessible at [http://localhost:8080](http://localhost:8080).

## 🧪 Tests & Quality

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

## 🐳 Docker (Example)

A `compose.yml` file is provided to facilitate deployment. Here is a configuration example:

```yaml
services:
  slim:
    image: semhoun/webserver
    container_name: slim-app
    restart: always
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=dev
      - DEBUG_MODE=true
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

To launch the environment with Docker:

```bash
docker compose up -d
```

## 📝 Notes

- Ensure that the `var` folder has write permissions.
- Default credentials: **admin** / **admin**.
- The project includes an error rendering system (HTML/JSON) based on the `Accept` header.

## 📄 License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.