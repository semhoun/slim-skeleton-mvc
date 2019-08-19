# Slim 4 MVC Skeleton

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/2154b570bb974bb1ae6d4d40bcd75fd4)](https://app.codacy.com/app/semhoun/slim-skeleton-mvc?utm_source=github.com&utm_medium=referral&utm_content=semhoun/slim-skeleton-mvc&utm_campaign=Badge_Grade_Dashboard)

This is a simple web application skeleton project that uses the [Slim4 Framework](http://www.slimframework.com/):

- [PHP-DI](http://php-di.org/) as dependency injection container
- [Sim-Psr7](https://github.com/slimphp/Slim-Psr7) as PSR-7 implementation
- [Doctrine](https://github.com/doctrine/orm) as ORM
- [Twig](https://twig.symfony.com/) as template engine
- [Flash messages](https://github.com/slimphp/Slim-Flash)
- [Monolog](https://github.com/Seldaek/monolog)
- Console

## CAUTION

**The Slim Twig-View is still in active development and can introduce breaking changes. It is 
an alpha release. Of course you can use this skeleton, but be warned. As soon as
you update the Slim Twig-View, you might have to modify your code.**


## Prepare

1. Create your project:


   ```bash
   composer create-project -s dev semhoun/slim-skeleton-mvc [your-app]
   ```
2. Create database: `php cli.php app:init-db`


## Run it:

1. `cd [your-app]`
2. `php -S 0.0.0.0:8888 -t public/`
3. Browse to http://localhost:8888


### Notice

- Set `var`folder permission to writable when deploy to production environment
- Default login/password is *admin*/*admin*
- To generate Doctrine entities:`php bin/entities_generator.php`
  :warning: *Delete all entities before re-generate to update entities.*