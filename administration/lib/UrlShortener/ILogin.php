<?php
namespace UrlShortener;

interface ILogin {
	public function __construct($init);

	public function login($user, $password);
	
	public function logout();
	
	public function isLogged();
}