<?php
namespace App\Action\User;

use App\Action\Controller;
use App\Library\Util;
use PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Forgot extends Controller
{

  public function main(Request $request, Response $response, $args)
  {
    $msg = "";
    $post = [];

    if ($request->isPost()) {
      $post = $request->getParams();

      if (empty($post['email'])) {
        $msg = 'Please fill username or password';
      } else {
        $row = $this->db->get_row($this->db->prepare("SELECT u.* from wi_users u WHERE u.username=%s OR u.email=%s", $post['email'], $post['email']));

        if ($row) {
          $domainfrom = $_SERVER['SERVER_NAME'];
          // Create Token forgot password
          $util = new Util();
          $token = $util->getToken(16); // Get activation generated token ~ 16 char
          $tokenpass = $util->getToken(8);
          // Check if forgot token exist for this user
          $this->db->query($this->db->prepare("DELETE FROM wi_tokens WHERE user_id=%d AND tokentype='forgot'", $row->id));
          $this->db->query($this->db->prepare(
            "INSERT INTO wi_tokens
							( tokentype, user_id, token, tokenpass,create_date, expiry_date )
						 VALUES
						 	( 'forgot', %d, %s, %s, NOW(), DATE_ADD(NOW(),INTERVAL 15 MINUTE) )
						",
            $row->id,
            $token,
            password_hash($tokenpass, PASSWORD_BCRYPT)
          ));

          // Send Email Activation
          $link = "http://" . $domainfrom . "/forgot/reset/" . $row->id . "/" . $token . ':' . $tokenpass; // Link Activation
          $mail = new PHPMailer;
          $mail->CharSet = 'UTF-8';
          $mail->setFrom('noreply@' . $domainfrom, 'Trendy Ichiba');
          $mail->addAddress($post['email']); // Add a recipient
          // $mail->addAddress('ellen@example.com');                 // Name is optional
          // $mail->addReplyTo('info@example.com', 'Information');
          // $mail->addCC('cc@example.com');
          // $mail->addBCC('bcc@example.com');

          $mail->isHTML(true); // Set email format to HTML

          $mail->Subject = 'Password reset at ' . $domainfrom;

          // Use email template
          $mail->Body = $this->view->fetch('email/forgot_notif.twig', [
            'title' => "Password Reset",
            'link' => $link,
            'domainfrom' => $domainfrom,
            'donotreply' => true, // show do not reply to this email
          ]);

          // var_dump($link);

          if ($mail->send()) {
            $this->logger->info("Email forgot password to: " . $post['email'] . " sent succesfully!"); // log berhasil
            return $response->withRedirect('/forgot/sent', 302);
          } else {
            $this->logger->info("Email forgot password to: " . $post['email'] . " are Failed!"); // log gagal
            $msg = "Sorry. Server cannot send email, due to: " . $mail->ErrorInfo . ". Please contact Administrator";
          }

        } else {
          $msg = 'Username or email not found. Do you like to <a href="/signup">register</a>?';
        }
      }

    }

    $this->view->render($response, 'user/forgot/forgot.twig', [
      'msg' => $msg,
    ]);

    return $response;
  }

  public function send_email(Request $request, Response $response, $args)
  {
    $this->view->render($response, 'user/forgot/sent.twig');
    return $response;
  }

  public function reset_password(Request $request, Response $response, $args)
  {
    $msg = "";
    $showform = true;
    // Check token in table tokens
    $tokens = explode(':', $args['token']);
    $check = $this->db->get_row(
      $this->db->prepare("
				SELECT t.user_id, t.expiry_date
				FROM wi_tokens t
				WHERE t.tokentype = 'forgot' AND t.user_id=%d AND t.token=%s",
        $args['id'],
        $tokens[0]
      )
    );

    // date_default_timezone_set("Asia/Tokyo");
    if ($check->expiry_date < date('Y-m-d H:i:s')) {
      $msg = "Reset password token expired. Please create new request.";
      $showform = false;
    } else {
      // Second check password hash

      if ($request->isPost()) {
        $post = $request->getParams();
        if (strlen(trim($post['password'])) < 6) {
          $msg = "Password is less than 6 characters";
        } elseif ($post['password'] != $post['password_retype']) {
          $msg = "Password and password retype not match";
        } else {
          // update password
          $sql = $this->db->prepare("
						UPDATE wi_users SET password=%s WHERE id=%d",
            password_hash(trim($post['password']), PASSWORD_BCRYPT),
            $args['id']
          );
          $this->db->query($sql);
          $this->db->query($this->db->prepare("DELETE FROM wi_tokens WHERE user_id=%d AND tokentype='forgot'", $args['id']));
          return $response->withRedirect('/forgot/done', 302);
          // var_dump($sql, $post);
        }
      }
    }

    $this->view->render($response, 'user/forgot/reset.twig', [
      'msg' => $msg,
      'showform' => $showform,
    ]);
    return $response;
  }

  public function success(Request $request, Response $response, $args)
  {
    $this->view->render($response, 'user/forgot/success.twig', []);
    return $response;
  }

}
