<?php
interface ILogin {
	
	public function login($user, $password);
	
	public function logout();
	
	public function isLogged();
	
	public static function getInstance($init);
}

?>