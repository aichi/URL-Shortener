<?php
namespace UrlShortener;
/**
 * Interface ILogin must be fulfilled by class specified in configuration file as login manager.
 * @package UrlShortener
 */
interface ILogin {
	/**
	 * Constructor should initialize all necessary properties and connections.
	 * @param array $init Associative array with all config
	 */
	public function __construct($init);

	/**
	 * This method tries to log in user into application.
	 * @param string $user
	 * @param string $password This is password in plaintext from JS application because internally should be used
	 * 					different algorithm for hashing passwords. BCrypt library is included in this application.
	 * @return bool True when user is authenticated by its password.
	 */
	public function login($user, $password);

	/**
	 * Log out actually logged user.
	 * @return bool True when user is properly logged out.
	 */
	public function logout();

	/**
	 * Returns if user is logged.
	 * @return bool
	 */
	public function isLogged();
}