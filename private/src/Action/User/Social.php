<?php
namespace App\Action\User;

use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use ezSQL_mysqli;
use App\Action\Controller;
use App\Library\Util;
use Hybrid_Auth;
use Hybrid_Endpoint;

final class Social extends Controller
{
	public function facebook(Request $request, Response $response, $args)
	{
		return $this->login($response, 'Facebook');
	}

	public function google(Request $request, Response $response, $args)
	{
		// must enable Google contact API and Google+ API
		// http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
		return $this->login($response, 'Google');	
	}

	private function login(Response $response, $socialName)
	{
		try {
			$hybridauth = new Hybrid_Auth( $this->settings['hybridauth'] );
			$fb = $hybridauth->authenticate( $socialName );

			$user = $fb->getUserProfile();
			$email = $user->emailVerified ? $user->emailVerified : $user->email;

			// search identifier
			$social = $this->db->get_row(
				$this->db->prepare( "
					SELECT * FROM wi_social_users 
					WHERE social_name=%s AND identifier=%s", 
					$socialName, 
					$user->identifier 
				)
			);

			if ($social) {

				// Social connect found

				$checkUser = $this->db->get_row(
					$this->db->prepare( "SELECT * FROM wi_users WHERE id=%d AND NOT delete_flag", $social->user_id )
				);

				if ($checkUser) {
					$this->process_login($checkUser);
					return $response->withRedirect('/dashboard', 302);
				} else {
					// Delete all tokens
					$this->db->query(
						$this->db->prepare( "
							DELETE * FROM wi_social_users 
							WHERE identifier=%s", 
							$user->identifier 
						)
					);

					// Continue to signup 
					$_SESSION['signup'] = $user;
					$_SESSION['signup']->social = $socialName;

					return $response->withRedirect('/social/signup', 302);
				}

				return $response->withRedirect('/', 302);

			} else {
				// Check email exists ?
				$checkEmail = $this->db->get_row(
					$this->db->prepare( "SELECT * FROM wi_users WHERE email=%s", $email )
				);
				if ($checkEmail) {
					// User exist --> Offer to connect to this user [ or just directly login as users ]
					// Update social_users connect
					$sql = $this->db->prepare(
						"INSERT INTO wi_social_users
							(user_id, social_name, identifier, profileURL, photoURL, create_date)
							VALUES (%d, %s, %s, %s, %s, NOW() )
						",
						$checkEmail->id, $socialName, $user->identifier, $user->profileURL, $user->photoURL
					);

					$this->db->query($sql);

					$this->process_login($checkEmail);

					return $response->withRedirect('/dashboard', 302);

				} else {
					// Offer to signup
					$_SESSION['signup'] = $user;
					$_SESSION['signup']->social = $socialName;
					return $response->withRedirect('/social/signup', 302);
				}
			}
			return $response;
		} catch( Exception $e ){
			return $response;
		}
		return $response;
	}

	public function signup(Request $request, Response $response, $args)
	{
		if (! isset($_SESSION['signup'])) {
			return $response->withRedirect('/signup',302);
		}

		$msg = "";
		$post = [];
		$domainfrom = $_SERVER['SERVER_NAME'];

		if ($request->isPost()) {
			$post = $request->getParams();

			if ( strlen($post['username']) < 4) {
				$msg = _("Username less than 4 characters");
			} elseif (! ctype_alnum($post['username']) ) {
				$msg = _("Username must use alphabet or number, without spaces");
			} elseif ( empty($post['mobile'])) {
				$msg = _("Mobile phone number is required");
			} elseif ( empty($post['nickname'])) {
				$msg = _("Display name is required");	
			} else {
				// Save and direct login
				// Create user and save social token ...
				$sql = $this->db->prepare(
					"INSERT INTO wi_users
						( email, username, mobile, nickname, active_flag, create_date )
					 VALUES 
					 	(%s, %s, %s, %s, 1, NOW() )	
					", 
					$post['email'],
					$post['username'],
					$post['mobile'],
					$post['nickname'],
					$token
				);
				$result = $this->db->query( $sql );
        		$userID =  $this->db->insert_id;

        		$util = new Util;
				$token = $util->getToken(8);
				$tokenpass = $util->getToken(8);
				
				// update remember token
				$this->db->query( $this->db->prepare( "DELETE FROM wi_tokens WHERE user_id=%s AND tokentype='web'", $userID ) );
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
						$userID,
						$token,
						password_hash($tokenpass, PASSWORD_BCRYPT)
					)
				);

				// Save connected social
				$sql = $this->db->prepare(
					"INSERT INTO wi_social_users
						(user_id, social_name, identifier, profileURL, photoURL, create_date)
						VALUES (%d, %s, %s, %s, %s, NOW() )
					",
					$userID, $_SESSION['signup']->social, $_SESSION['signup']->identifier, $_SESSION['signup']->profileURL, $_SESSION['signup']->photoURL
				);

				$this->db->query($sql);

				// Send Welcome Email

				// Remove signup session
				session_destroy();

				$checkEmail = $this->db->get_row(
					$this->db->prepare( "SELECT * FROM wi_users WHERE id=%s", $userID )
				);

				$this->process_login($checkEmail);

				return $response->withRedirect('/dashboard');

			}

		} else {
			$user = $_SESSION['signup'];
			$post['email'] = $user->emailVerified ? $user->emailVerified : $user->email;
			$post['nickname'] = $user->displayName;
		}

		$this->view->render($response, 'user/signup/social_signup.twig',[
			'msg' => $msg,
			'social' => $_SESSION['signup'],
			'r' => $post,
			'domain' => $domainfrom,
		]);

		return $response;
	}

	public function success(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'user/signup/social_signup_success.twig' ,[]);
		return $response;
	}

	private function process_login($row) 

	{
		$session = new \App\Library\Session();
		$session->updateSession($row);

		$this->db->query( $this->db->prepare("UPDATE wi_users SET lastlogin_date=NOW() WHERE id=%d", $row->id ) );

		$util = new Util;
		$token = $util->getToken(8);
		$tokenpass = $util->getToken(8);

		$this->db->query( $this->db->prepare( "DELETE FROM wi_tokens WHERE user_id=%s AND tokentype='web'", $row->id ) );
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
		setcookie("t", $token .':' . $tokenpass, time()+(3600*24*365), '/' );  /* expire in 1 year */

	}

	/*
		> ["identifier"]=> string(17) "10153349405008846" 
		["webSiteURL"]=> string(0) "" 
		> ["profileURL"]=> string(62) "https://www.facebook.com/app_scoped_user_id/10153349405008846/" 
		> ["photoURL"]=> string(73) "https://graph.facebook.com/10153349405008846/picture?width=150&height=150" 
		> ["displayName"]=> string(12) "Sunaryo Hadi" 
		["description"]=> string(0) "" 
		["firstName"]=> string(7) "Sunaryo" 
		["lastName"]=> string(4) "Hadi" 
		["gender"]=> string(4) "male" 
		["language"]=> string(5) "en_US" ["age"]=> NULL ["birthDay"]=> NULL ["birthMonth"]=> NULL ["birthYear"]=> NULL 
		> ["email"]=> string(21) "sunaryohadi@gmail.com" 
		> ["emailVerified"]=> string(21) "sunaryohadi@gmail.com" 
		["phone"]=> NULL ["address"]=> NULL ["country"]=> NULL ["region"]=> string(0) "" ["city"]=> NULL ["zip"]=> NULL ["job_title"]=> NULL ["organization_name"]=> NULL
	*/

	public function callback(Request $request, Response $response, $args)
	{
		// Cuma gini aja callback ternyata ... --> Hybrid_Endpoint::process()
		// Copy index.php untuk callback --> gimana masuk di routing Slim
		// *** spend a day to figure out
		Hybrid_Endpoint::process();
		return $response;
	}		

}
