<?php
require_once '../config.php';

$directory = '.'.DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR;
require_once $directory."Autoloader.php";
Autoloader::register($directory);

session_start();

try {
	$app = new UrlShortener\Application($CONFIG);

	$app->execute();
} catch (Exception $e) {
	print_r($e);
	//some nice 500 page
}

