<?php
return [

  'settings' => [
    // Slim Settings
    'determineRouteBeforeAppMiddleware' => true,
    'displayErrorDetails' => true,

    // View settings
    'view' => [
      // change to /templates/dashboard for dashboard design
      'template_path' => __DIR__ . '/templates',
      'twig' => [
        'cache' => __DIR__ . '/../tmp/cache',
        'debug' => true,
        'auto_reload' => true,
      ],
    ],

    // monolog settings
    'logger' => [
      'name' => 'app',
      'path' => __DIR__ . '/../tmp/app.log',
    ],

    // db settings
    'db' => [
      'dbuser' => 'user',
      'dbpassword' => 'password',
      'dbname' => 'dbname',
      'dbhost' => 'localhost',
    ],

    // You can add more settings here

  ],

];
