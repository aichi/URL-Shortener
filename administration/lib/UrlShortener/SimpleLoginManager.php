<?php
namespace UrlShortener;

/**
 * Simple login manager should be used for small project
 * where user names and password hashes are stored in config.
 *
 * This class expects values in form
 * $CONFIG['production'] = array(
 * 	"users": array(
 * 		"user_name" : "$2a$08$axn4BPiiWmNCg.iqusznxOs.RUSejWqvlBA364cFUOYGUdzq/vGeS", //bcrypt_hash "admin"
 * 		...
 * 	)
 * );
 */
class SimpleLoginManager implements ILogin{
	private $users = array();

	public function __construct($init) {
		if (!empty($init['users']))
			$this->users = $init['users'];
	}
	
	public function login($user, $password) {
		if (key_exists($user ,$this->users) && \Bcrypt::check($password, $this->users[$user])) {
			$_SESSION['logged'] = true;
			return true;
		}
		return false;
	}
	
	public function logout() {
		$_SESSION['logged'] = false;
		session_destroy();
		return true;
	}
	
	public function isLogged() {
		if(isset($_SESSION['logged']))
			return $_SESSION['logged'];
		return false;
	}
}