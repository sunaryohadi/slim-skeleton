<?php
namespace App\Action\User;

use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
// use Slim\Flash\Messages;
use ezSQL_mysqli;
use PHPMailer;
use App\Action\Controller;
use App\Library\Util;

final class Signup extends Controller
{

	public function register(Request $request, Response $response, $args)
	{
		if (isset( $_SESSION['user']) && $_SESSION['user']['login'] ) 
			return $response->withRedirect('/dashboard',302);
		
		$msg ="";
		$post = [];
		$domainfrom = $_SERVER['SERVER_NAME'];

		if ($request->isPost()) {

			$post = $request->getParams();
			$post['username'] = trim($post['username']);

			if ( ! filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
				$msg = _("Email is not valid");
			} elseif ( strlen($post['username']) < 4) {
				$msg = _("Username less than 4 characters");
			} elseif (! ctype_alnum($post['username']) ) {
				$msg = _("Username must use alphabet or number");
			} elseif ( preg_match('/\s/',$post['username']) ) {
				$msg = _("Username must not contains spaces");
			} elseif ( strlen($post['password']) < 6) {
				$msg = _("Password less than 6 characters");
			} elseif ( empty($post['mobile'])) {
				$msg = _("Mobile phone number is required");
			} elseif ( empty($post['nickname'])) {
				$msg = _("Display name is required");	
			} else {

				// check email sudah digunakan
				$checkemail = $this->db->get_var(
					$this->db->prepare("SELECT id FROM wi_users WHERE email=%s", $post['email'])
				);

				if ($checkemail) {
					$msg = _("This email has been used");
				} else {

					// check username sudah digunakan
					$checkusername = $this->db->get_var(
						$this->db->prepare("SELECT id FROM wi_users WHERE username=%s", $post['username'])
					);

					if ($checkusername) {
						$msg = _("This username has been used");
					} else {

						$util = new Util();

						$token = $util->getToken(16); // Get activation generated token ~ 16 char

						// Create user and save token ...
						$sql = $this->db->prepare(
							"INSERT INTO wi_users
								( email, username, password, mobile, nickname, create_date )
							 VALUES 
							 	(%s, %s, %s, %s, %s, NOW() )	
							", 
							$post['email'],
							$post['username'],
							password_hash( trim($post['password']), PASSWORD_BCRYPT ),
							$post['mobile'],
							$post['nickname'],
							$token
						);
						$result = $this->db->query( $sql );
	            		$userID =  $this->db->insert_id;

	            		$this->db->query( $this->db->prepare(
							"INSERT INTO wi_tokens
								( tokentype, user_id, token, create_date )
							 VALUES 
							 	( 'activation', %d, %s, NOW() )	
							", 
							$userID,
							$token
						) );

						// Send Email Activation
						$link = "http://" . $domainfrom. "/signup/activate/" . $userID . "/" . $token ;  // Link Activation
						$mail = new PHPMailer;
						$mail->CharSet = 'UTF-8';
						$mail->setFrom('noreply@'.$domainfrom , $this->settings['app']['title'] );
						$mail->addAddress($post['email']);     						// Add a recipient
						// $mail->addAddress('ellen@example.com');               	// Name is optional
						// $mail->addReplyTo('info@example.com', 'Information');
						// $mail->addCC('cc@example.com');
						// $mail->addBCC('bcc@example.com');

						$mail->isHTML(true);                                  		// Set email format to HTML

						$mail->Subject = _('Your registration activation at ') . $domainfrom;

						// Use email template
						$mail->Body = $this->view->fetch( 'email/signup_notif.twig',[ 
								'title' => _("Email confirmation"),
								'link' => $link,
								'donotreply' => true, 	// show do not reply to this email
							]);

						if ( $mail->send()) {
			    			$this->logger->info("Email activation to: " . $post['email'] . " sent succesfully!" ); // log berhasil
			    			return $response->withRedirect('/signup/email', 302);
						} else {
							$this->logger->info("Email activation to: " . $post['email'] . " are Failed!" ); // log gagal
							$msg = _("Sorry. Server cannot send email, due to: ") . $mail->ErrorInfo . ". " . _("Please contact Administrator");
						}
					}

				}
			}

		}

		$this->view->render($response, 'user/signup/signup.twig' ,[
			'r' => $post,
			'msg' => $msg,
			'domain' => $domainfrom,
		]);

		/*
		// Test preview email
		$this->view->render($response, 'email/signup_notif.twig' ,[
			'title' => "Testos",
			'link' => $link,
			'donotreply' => true,
		]);
		*/

		return $response;
	}

	public function email_sent(Request $request, Response $response, $args)
	{

		$this->view->render($response, 'user/signup/email.twig' ,[]);

		return $response;
	}

	public function activation(Request $request, Response $response, $args)
	{
		$msg = "";
		

		$post = $request->getParams();

		// Check token in table tokens
		$check = $this->db->get_row(
			$this->db->prepare("SELECT u.active_flag, t.token, u.email FROM wi_tokens t INNER JOIN wi_users u ON t.user_id = u.id  WHERE t.tokentype = 'activation' AND t.user_id=%d AND t.token=%s", $args['id'], $args['token'])
		);

		if ( ! $check || $check->token != $args['token'] ) {
			$this->view->render($response, 'user/signup/token_invalid.twig' ,[]);
			return $response;
		}

		if ( $check->active_flag ) {
			// Delete token
			$this->db->query(
				$this->db->prepare("DELETE FROM wi_tokens WHERE tokentype='activation' AND user_id=%d", $args['id'])
			);

			// Token is already activated
			$this->view->render($response, 'user/signup/already_activated.twig' ,[]);

		} else {
			// Delete token
			$this->db->query(
				$this->db->prepare("DELETE FROM wi_tokens WHERE tokentype='activation' AND user_id=%d", $args['id'])
			);

			// Activate Account 
			$this->db->query(
				$this->db->prepare("UPDATE wi_users SET active_flag=1 WHERE id=%d", $args['id'])
			);

			// Activated directly
			$this->view->render($response, 'user/signup/success.twig' ,[]);
		}

		return $response;
	}


}
