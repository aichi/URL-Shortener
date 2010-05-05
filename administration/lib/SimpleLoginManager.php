<?php
class SimpleLoginManager extends TObjectStatic implements ILogin{
	protected static $instance = null;
	
	
	public function login($user, $password) {return true;}
	
	public function logout() {}
	
	public function isLogged() {return true;}
	
	public static function getInstance() {
		if (SimpleLoginManager::$instance == null) {
			$pm = new SimpleLoginManager();
//			if (!$pm->init()) {
//				return false;
//			}
			SimpleLoginManager::$instance = $pm;
		}
		return SimpleLoginManager::$instance;
	}
}