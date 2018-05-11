<?php


// Email signin
$app->map(['GET', 'POST'], '/login', 'App\Action\User\Auth:login')->setName('login');
$app->get('/logout', 'App\Action\User\Auth:logout')->setName('logout');
$app->group('/signup', function () {  
	$this->map(['GET', 'POST'], '/activate/{id}/{token}', 'App\Action\User\Signup:activation');
	$this->map(['GET', 'POST'], '/email', 'App\Action\User\Signup:email_sent');
	$this->map(['GET', 'POST'], '[/]', 'App\Action\User\Signup:register')->setName('signup');
});
$app->group('/forgot', function () {
	$this->map(['GET', 'POST'],'/reset/{id}/{token}', 'App\Action\User\Forgot:reset_password');
	$this->get('/sent', 'App\Action\User\Forgot:send_email');
	$this->get('/done', 'App\Action\User\Forgot:success');
	$this->map(['GET', 'POST'], '[/]', 'App\Action\User\Forgot:main');
});

// Social Signin
$app->map(['GET', 'POST'], '/google', 'App\Action\User\Social:google');
$app->map(['GET', 'POST'], '/fb', 'App\Action\User\Social:facebook');
$app->map(['GET', 'POST'], '/social/signup', 'App\Action\User\Social:signup');
$app->map(['GET', 'POST'], '/social/signup/success', 'App\Action\User\Social:success');
$app->group('/callback', function () {	
	$this->map(['GET', 'POST'], '[/]', 'App\Action\User\Social:callback');
});

$app->group('/profile', function () {
	$this->map(['GET', 'POST'], '/password', 'App\Action\User\Profile:password');
	$this->map(['GET', 'POST'], '[/]', 'App\Action\User\Profile:main')->setName('profile');
})->add( new App\Middleware\Login() );