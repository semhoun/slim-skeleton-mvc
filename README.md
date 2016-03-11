# Slim 3 MVC Skeleton

This is a simple skeleton project for Slim 3 that includes Doctrine, Twig, Flash messages and Monolog.

Base on https://github.com/akrabat/slim3-skeleton
and https://github.com/vhchung/slim3-skeleton-mvc

## Prepare

1. Create your project:

       `$ composer create-project -n -s dev agustim/slim3-skeleton-mvc-sqlite your-app`

2. Create database: `$ cat sql/blog.sql | sqlite3 sql/blog.sqlite`
3. Generate models (Doctrine entities):

```

$ cd your-app
$ php entities_generator.php

```

 Add namespace for each model: `namespace App\Model;`

 Notice: Delete all models before re-generate to update models.

### Run it:

1. `$ cd your-app`
2. `$ php -S 0.0.0.0:8888 -t public/`
3. Browse to http://localhost:8888

### Notice

Set `logs` and `cache` folder permission to writable when deploy to production environment

## Key directories

* `app`: Application code
* `app/src`: All class files within the `App` namespace
* `app/templates`: Twig template files
* `cache/twig`: Twig's Autocreated cache files
* `log`: Log files
* `public`: Webserver root
* `vendor`: Composer dependencies
* `sql`: sql dump file for sample database

## Key files

* `public/index.php`: Entry point to application
* `app/settings.php`: Configuration
* `app/dependencies.php`: Services for Pimple
* `app/middleware.php`: Application middleware
* `app/routes.php`: All application routes are here
* `app/src/controllers/HomeController.php`: Controller class for the home page
* `app/src/models/Post.php`: Entity class for post table
* `app/templates/home.twig`: Twig template file for the home page
