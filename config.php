<?php
$CONFIG = array();
$CONFIG['development'] = array(
	'persistentManager' => 'UrlShortener\PersistentManager',
	'loginManager'		=> 'UrlShortener\SimpleLoginManager',
	'shortenerConnector'=> 'UrlShortener\BitlyConnector',
	'connection'		=> array(
		'server' => "localhost",
		'user' => "root",
		'password' => "",
		'database' => "url_shortener",
		'table' => 'url_shorten'
	),
	'shortenerConnectorConfig' => array(
		'url'	  => 'http://bit.ly/',
		'login'   => 'test',
		'apikey'  => 'R_xxx'
	),
	'shortenUrl' => 'http://example.com/',
	'users' => array(
		"admin" => '$2a$08$axn4BPiiWmNCg.iqusznxOs.RUSejWqvlBA364cFUOYGUdzq/vGeS'//admin:admin
	)
);
$CONFIG['production'] = array(
	'persistentManager'	=> 'UrlShortener\PersistentManager',
	'loginManager'		=> 'UrlShortener\CzechdesignLoginManager',
	'shortenerConnector'=> 'UrlShortener\BitlyConnector',
	'connection'		=> array(	
		'server' => 'localhost',
		'user' => 'root',
		'password' => '',
		'database' => 'url_shortener',
		'table' => 'url_shorten'
	),
	'shortenerConnectorConfig' => array(
		'url'	  => 'http://bit.ly/',
		'login'   => 'test',
		'apikey'  => 'R_xxx'
	),
	'shortenUrl' => 'http://example.com/'

);

?>