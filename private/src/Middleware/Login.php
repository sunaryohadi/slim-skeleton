<?php 
namespace App\Middleware;

class Login
{
	public function __invoke($request, $response, $next)
	{
		if (  ! isset($_SESSION['user']['login']) ) {
			return $response = $response->withRedirect('/login', 403);
		}

		$response = $next($request, $response);
		
		return $response;
	}
}