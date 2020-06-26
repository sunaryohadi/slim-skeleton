<?php
namespace App\Middleware;

class ManagerLogin
{
  public function __invoke($request, $response, $next)
  {
    if (!isset($_SESSION['user']['login'])) {
      return $response = $response->withRedirect('/login', 302);
    } else {
      if (!in_array($_SESSION['user']['role'], ['Administrator', 'Manager', 'Moderator'])) {
        return $response = $response->withRedirect('/dashboard', 302);
      }
    }

    $response = $next($request, $response);

    return $response;
  }
}
