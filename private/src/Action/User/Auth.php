<?php
namespace App\Action\User;

use App\Action\Controller;
use App\Library\Util;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Auth extends Controller
{

  public function login(Request $request, Response $response, $args)
  {

    if (isset($_SESSION['user']) && isset($_SESSION['user']['login']) && $_SESSION['user']['login']) {
      return $response->withRedirect('/dashboard', 302);
    }

    $msg = "";
    $post = [];

    // Init CSRF
    $slimGuard = new \Slim\Csrf\Guard();
    $slimGuard->validateStorage();
    $csrfNameKey = $slimGuard->getTokenNameKey();
    $csrfValueKey = $slimGuard->getTokenValueKey();

    if ($request->isPost()) {
      $post = $request->getParams();

      // Check validate token
      $csrfPass = $slimGuard->validateToken($post[$csrfNameKey], $post[$csrfValueKey]);

      if (!$csrfPass) {
        $msg = "You have login from this site";
      } elseif (empty($post['username']) || empty($post['password'])) {
        $msg = "Username/Password is required";
      } else {
        $row = $this->db->get_row($this->db->prepare("SELECT u.* from wi_users u WHERE u.username = %s", $post['username']));

        if ($row) {

          if (password_verify($post["password"], $row->password)) {

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
            $session = new \App\Library\Session();
            $session->updateSession($row);

            if (isset($post['remember_me'])) {
              $util = new Util;
              $token = $util->getToken(8);
              $tokenpass = $util->getToken(8);

              // update token
              $this->db->query($this->db->prepare("DELETE FROM wi_tokens WHERE user_id=%s AND tokentype='web'", $row->id));
              $this->db->query(
                $this->db->prepare("INSERT INTO wi_tokens (
										user_id,
										token,
										tokenpass,
										create_date,
										expiry_date
									) VALUES (
										%d,
										%s,
										%s,
										NOW(),
										DATE_ADD(NOW(),INTERVAL 365 DAY)
									)",
                  $row->id,
                  $token,
                  password_hash($tokenpass, PASSWORD_BCRYPT)
                )
              );

              // update cookie
              setcookie("t", $token . ':' . $tokenpass, time() + (3600 * 24 * 365), '/'); /* expire in 1 year */

            }

            // update lastlogin
            $this->db->query(
              $this->db->prepare("UPDATE wi_users SET lastlogin_date=NOW() WHERE id=%d", $row->id)
            );

            return $response->withRedirect('/dashboard', 302);
          } else {
            $msg = "Password incorrect";
          }

        } else {
          $msg = "Username/Password incorrect";
        }
      }
    }

    // Generate new CSRF tokens ~ Always fresh token
    $keyPair = $slimGuard->generateToken();

    //
    // var_dump($keyPair);

    $this->view->render($response, 'user/login/login.twig', [
      'msg' => $msg,
      'csrfNameKey' => $csrfNameKey,
      'csrfValueKey' => $csrfValueKey,
      'keyPair' => $keyPair,
    ]);

    return $response;
  }

  public function logout(Request $request, Response $response, $args)
  {
    if ($_SESSION["user"]["login"]) {
      // Delete token in table tokens
      if (isset($_COOKIE['t'])) {
        $cookies = explode(':', $_COOKIE['t']);
        $this->db->query(
          $this->db->prepare(
            "DELETE FROM wi_tokens WHERE token=%s",
            $cookies[0]
          )
        );
      }

      // unset session
      unset($_SESSION['user']);
      session_destroy();
      // unset cookies
      setcookie("t", "", 1, "/");
    }

    return $response->withRedirect('/', 302);
  }

}
