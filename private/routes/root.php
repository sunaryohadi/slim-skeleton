<?php
// Routes
include "user.php"; 


$app->get('/', 'App\Action\Home:index')->setName('homepage');

// Grouping actions
$app->group('/crud', function () {  
	$this->map(['GET', 'POST'], '/del/{ID}', 'App\Action\SimpleCRUD:delete');
	$this->map(['GET', 'POST'], '/edit/{ID}', 'App\Action\SimpleCRUD:edit');
	$this->map(['GET', 'POST'], '/add', 'App\Action\SimpleCRUD:add');
	$this->get('/search', 'App\Action\SimpleCRUD:search');
	$this->get('[/]', 'App\Action\SimpleCRUD:main');
});