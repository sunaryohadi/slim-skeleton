<?php
namespace App\Action\User;

use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Flash\Messages;
use ezSQL_mysqli;
use PHPMailer;
use App\Action\Controller;
use App\Library\Util;

final class Profile extends Controller
{

	public function main(Request $request, Response $response, $args)
	{

		
		if ( ! isset($_SESSION['user']['login'] ) ||  ! $_SESSION['user']['login'] ) 
			return $response->withRedirect('/login',302);

		$msg = "";
		$msgclass = "error";
		$post = [];

		if ($request->isPost()) {
			$post = $request->getParams();

			if ( strlen($post['nickname']) < 4 ) {
				$msg = "Display name is too short"; 
			} else {

				$sql = $this->db->prepare("
					UPDATE wi_users SET 
						nickname=%s,
						fullname=%s,
						address=%s,
						city_id=%d,
						area_id=%d,
						zip=%s,
						state=%s,
						country_iso=%s,
						mobile=%s,
						phone=%s
					WHERE id=%d",
						$post['nickname'],
						$post['fullname'],
						$post['address'],
						$post['city_id'],
						$post['area_id'],
						$post['zip'],
						$post['state'],
						$post['country_iso'],
						$post['mobile'],
						$post['phone'],
					$_SESSION['user']['id']
				);

				$this->db->query($sql);
				// var_dump($post);
				$msg = "Data is updated";
				$msgclass = "success";
			}
		}

		$row = $this->db->get_row(
			$this->db->prepare("SELECT * FROM wi_users WHERE id=%d", $_SESSION['user']['id'] )
		);

		if ( !$row ) return $response->withRedirect('/login',302);

		$countries = $this->db->get_results("SELECT iso, country FROM wi_country");
		$cities = $this->db->get_results("
			SELECT a.id, CONCAT( a.title, IF(a.government = 'Kota',' Kota', ' Kab.') , ', ', pa.title) as title 
			FROM wi_area a 
				LEFT JOIN wi_area pa ON a.parent_id=pa.id 
			WHERE a.level=2
			ORDER BY pa.id ASC, FIELD(a.government, 'Kota','Kabupaten'), a.title ASC");

		$areas = $this->db->get_results(
			$this->db->prepare("SELECT a.id, CONCAT(  a.title, ', Kec. ', pa.title) as title FROM wi_area a 
					LEFT JOIN wi_area pa ON pa.id=a.parent_id 
				WHERE pa.parent_id=%d AND a.level=4
				ORDER BY a.code
				", 
				$row->city_id
			)
		);
		
		$this->view->render($response, 'user/profile/profile.twig',[
			'msg' => $msg,
			'msgclass' => $msgclass,
			'r' => $row,
			'cities' => $cities,
			'areas' => $areas,
			'countries' => $countries,
		]);

		return $response;
	}

	public function password(Request $request, Response $response, $args)
	{
		if ( ! $_SESSION['user']['id'] ) return $response->withRedirect('/login',302);

		$msg = "";
		$msgclass = "error";
		$post = [];

		if ($request->isPost()) {
			$post = $request->getParams();

			if ( strlen($post['new_password']) < 6 ) {
				$msg = "Password require at least 6 characters"; 
			} elseif ( $post['new_password'] != $post['new_password_retype']) {
				$msg = "Password retype not identical"; 
			} else {
				
				$this->db->query(
					$this->db->prepare(
						"UPDATE wi_users SET password=%s WHERE id=%d",
						password_hash( trim($post['new_password']), PASSWORD_BCRYPT ),
						$_SESSION['user']['id']
					)
				);

				// Delete token in table tokens
				if ( isset($_COOKIE['t']) ) {
					$cookies = explode(':', $_COOKIE['t']);
					$this->db->query(
						$this->db->prepare(
							"DELETE FROM wi_tokens WHERE token=%s",
							$cookies[0]
						)
					);
				}

				// unset session
				// unset($_SESSION['user']);
				session_destroy();
				// unset cookies
				setcookie("t", "", 1, "/");

				$msg = 'Password updated. Please <a href="/login">click here</a> to re-login.';
				$msgclass = "success";
				
			}
		} 

		$this->view->render($response, 'user/profile/password.twig',[
			'msg' => $msg,
			'msgclass' => $msgclass,
		]);

		return $response;
	}

}