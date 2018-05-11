<?php
return [

	'settings' => [
		// Slim Settings
		'determineRouteBeforeAppMiddleware' => true,
		'displayErrorDetails' => true,

		// View settings
		'view' => [
			// change to /templates/dashboard for dashboard design
			'template_path' => __DIR__ . '/templates/flexbase',
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
			'dbhost' => 'localhost'
		],

		// Google recaptcha Key settings
		'recaptcha' => [
			'siteKey' => '',
			'secretKey' => '',
		],

		'hybridauth' => [
			"base_url" => "http://yoursite.com/callback/",
			"providers" => [
				"Google" => [
					"enabled" => true,
					"keys"    => [ 
						"id"    => "key", 
						"secret"  => "secret" 
					],
					"scope"   => "https://www.googleapis.com/auth/userinfo.profile ". // optional
								 "https://www.googleapis.com/auth/userinfo.email"   , // optional
					// "access_type"     => "offline",   // optional
					// "approval_prompt" => "force",     // optional
					// "hd"              => "domain.com" // optional
				],
				"Facebook" => [
					"enabled" => true,
					"keys"    => [ 
						"id" => "key", 
						"secret" => "secret" 
					],
					"scope"   => ['email'], // , 'user_about_me', 'user_birthday', 'user_hometown' ], // optional
					"display" => "popup" // optional
				]

			]
		],

	],
	
];
