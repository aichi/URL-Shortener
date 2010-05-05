<?php
$CONFIG = array();
$CONFIG['development'] = array(
	'persistentManager' => "PersistentManager",
	'loginManager'		=> "SimpleLoginManager",
	'connection'		=> array(	
		'server' => 'localhost',
		'user' => 'root',
		'password' => '',
		'database' => 'url_shortener',
		'table' => 'url_shorten'
	),
	'bitly' => array(
		'login' => 'test',
		'apikey' => 'R_xxx'
	)
);
$CONFIG['production'] = array(
	'persistentManager'	=> "PersistentManager",
	'loginManager'		=> "SimpleLoginManager",
	'connection'		=> array(	
		'server' => 'localhost',
		'user' => 'root',
		'password' => '',
		'database' => 'url_shortener',
		'table' => 'url_shorten'
	),
	'bitly' => array(
		'login' => 'test',
		'apikey' => 'R_xxx'
	)
);