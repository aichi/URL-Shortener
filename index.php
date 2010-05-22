<?php
include_once "./config.php";

$server = (strpos($_SERVER["HTTP_HOST"], 'localhost') === false) ? 'production':'development' ;
$db_conf = $CONFIG[$server]['connection'];

//url parameter not found
if (!isset($_GET['url'])) {
	header("HTTP/1.0 400 Bad request");
	exit;
}

$url = strtolower($_GET['url']);

//url parameter does not contain allowed characters - numbers and letters and .-_
if (!preg_match('/^([a-zA-Z0-9\.\-_])+$/', $url)) {
	header("HTTP/1.0 415 Unsupported Media Type");
	exit;
}

$conn = @mysql_connect($db_conf['server'], $db_conf['user'], $db_conf['password']);
//not connected or bad username and/or password
if (!$conn || !mysql_select_db($db_conf['database'], $conn)) {
	header("HTTP/1.0 500 Internal Server Error");
	exit;
} 

$query = "SELECT bitlyHash FROM ".$db_conf['table']." WHERE idUrlShorten='$url' LIMIT 1";
$result = mysql_query($query);
//bad table
if (!$result) {
	header("HTTP/1.0 500 Internal Server Error");
	exit;
}

$row = mysql_fetch_array($result);
//no result for inputed local hash
if (!$row) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

//everything OK, redirect to bit.ly
header("HTTP/1.0 301 Moved");
header("Location: http://bit.ly/".$row[0]);
exit;
?>