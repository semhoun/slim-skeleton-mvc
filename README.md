# Slim 3 MVC Skeleton

This is a simple skeleton project for Slim 3 that includes Doctrine, Twig, Flash messages, Monolog and JWT token authentication

Base on agustim/slim3-skeleton-mvc-sqlite

## Prepare

1. Create your project:


   ```bash
   composer create-project --repository-url https://gitlab.com/semhoun/slim3-skeleton-mvc/raw/master/packages.json -s dev semhoun/slim3-skeleton-mvc your-app
   ```
2. Copy the settings file `cp config/settings.php.dist config/settings.php`
3. Create database: `php cli.php app:init-db`
4. Generate Doctrine entities: `php bin/entities_generator.php`
:warning: *Delete all entities before re-generate to update entities.*

## Run it:

1. `cd your-app`
2. `php -S 0.0.0.0:8888 -t public/`
3. Browse to http://localhost:8888

### Notice

Set `var`folder permission to writable when deploy to production environment

Default login/password is *admin*/*admin*

## Key directories

* `bin`:Application tools
* `config`: Application middleware and general settings
* `src`: Application code
* `src/Controller`: All Controller files
* `src/Entity`: Doctrine entities
* `src/Lib`: Authentication libraries
* `src/Command`: CLI commands
* `templates`: Twig template files
* `var/cache/twig`: Twig's Autocreated cache files
* `var/log`: Log files
* `public`: Webserver root
* `vendor`: Composer dependencies

## Key files

* `public/index.php`: Entry point  to application and bootstrap
* `config/settings.php`: Configuration
* `config/dependencies.php`: Services for Pimple
* `config/middleware.php`: Application middleware
* `config/routes.php`: All application routes are here
* `src/Controller/HomeController.php`: Controller class for the home page
* `src/Entity/Post.php`: Entity class for post table
* `templates/home.twig`: Twig template file for the home page
* `src/Command/InitDB.php`CLI class to create initial database
