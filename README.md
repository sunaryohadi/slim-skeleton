# Slim 3 Skeleton

This is a simple skeleton project for Slim 3 that includes Twig, Flash messages, Monolog & ezSQL.

Also theres same component, which is mostly required.

Twig template using SASS and bourbon family. Also included sample of "simple CRUD".

## Requirements
* PHP 5.6.x or newer
* MySQL Server 5.x or newer

## Create your project:

    $ composer create-project -n -s dev sunaryohadi/slim3-skeleton my-private

### Database

* Create database and import crud.sql for sample daabase

### Run it:

1. `$ cd my-app`
2. `$ php -S 0.0.0.0:8888 -t web index.php`
3. Browse to http://localhost:8888

## Key directories

* `private`: Application code
* `private/src`: All class files within the `private` namespace
* `private/templates`: Twig template files
* `tmp/cache`: Twig's Autocreated cache files
* `tmp/log`: Log files
* `web`: Webserver root
* `private/routes`: Router file
* `private/vendor`: Composer dependencies
* `private/sass`: Sass files using bourbon.io

## Key files

* `web/index.php`: Entry point to Application
* `private/settings.php`: Configuration
* `private/dependencies.php`: Services for Pimple
* `private/middleware.php`: Application middleware
* `private/routes/root.php`: Main route are here
* `private/src/Action/HomeAction.php`: Action class for the home page
* `private/templates/main.twig`: Main base Twig Template
* `private/templates/home.twig`: Twig template file for the home page

## Credits
* https://github.com/akrabat/slim3-skeleton
* http://justinvincent.com/ezsql
* http://bourbon.io
