<?php
namespace UrlShortener;

/**
 * Simple login manager should be used for small project
 * where user names and password hashes are stored in config file.
 *
 * This class expects values in that form:
 * $CONFIG['production'] = array(
 * 	"users": array(
 * 		"user_name" : "$2a$08$axn4BPiiWmNCg.iqusznxOs.RUSejWqvlBA364cFUOYGUdzq/vGeS", //bcrypt_hash "admin"
 * 		...
 * 	)
 * );
 */
class SimpleLoginManager implements ILogin{
	/**
	 * @var array Associative array of users stored as 'user_name': 'password_hash' schema.
	 */
	private $users = array();

	/**
	 * Constructor stores all users from config file to local variable
	 * @param array $init
	 */
	public function __construct($init) {
		if (!empty($init['users']))
			$this->users = $init['users'];
	}

	/**
	 * Checks user against stored users list and password against Bcrypted hash. If user name and password are valid,
	 * user is stored to the session.
	 * @param string $user
	 * @param string $password
	 * @return bool
	 */
	public function login($user, $password) {
		if (key_exists($user, $this->users) && \Bcrypt::check($password, $this->users[$user])) {
			$_SESSION['logged'] = true;
			return true;
		}
		return false;
	}

	/**
	 * Removes actually logged user from session.
	 * @return bool
	 */
	public function logout() {
		$_SESSION['logged'] = false;
		session_destroy();
		return true;
	}

	/**
	 * Checks if there is any logged user stored in session.
	 * @return bool
	 */
	public function isLogged() {
		if(isset($_SESSION['logged']))
			return $_SESSION['logged'];
		return false;
	}
}