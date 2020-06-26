<?php
namespace App\Middleware;

class Auth
{

  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function __invoke($request, $response, $next)
  {
    if (!isset($_SESSION['user']['login']) && isset($_COOKIE['t'])) {
      // Lets check cookies
      $cookies = explode(':', $_COOKIE['t']);
      $row = $this->db->get_row(
        $this->db->prepare(
          "SELECT u.*, t.tokenpass as tokenpassword FROM wi_users u LEFT JOIN wi_tokens t ON u.id = t.user_id WHERE t.tokentype='web' AND t.token=%s",
          $cookies[0]
        )
      );

      if ($row && password_verify($cookies[1], $row->tokenpassword)) {
        $session = new \App\Library\Session();
        $session->updateSession($row);
        /*
      $_SESSION['user'] = [
      'login' => true,
      'id' => $row->id,
      'nickname' => $row->nickname,
      'lastlogin' => $row->lastlogin_date,
      'role' => $row->role,
      'country' => $row->country_iso,
      'currency' => $row->currency,
      'lang' => $row->language,
      ];
       */

      } else {
        // Clear cookies
        setcookie("t", "", 1, "/");
      }

    }

    // $response->getBody()->write('BEFORE');
    $response = $next($request, $response);
    // $response->getBody()->write('AFTER');

    return $response;
  }
}
