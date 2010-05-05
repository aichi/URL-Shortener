<?php
require_once '../config.php';

require_once './lib/TObject.php';
require_once './lib/TObjectStatic.php';
require_once './lib/Application.php';
require_once './lib/ILogin.php';
require_once './lib/IPersistence.php';

session_start();

$app = Application::getInstance($CONFIG);

if (!isset($_REQUEST['page'])) {
	$_REQUEST['page'] = 'urlList';
}

if ($app->isUserLogged() && in_array(strtolower($_REQUEST['page']), $app->allowedPages() ) ) {
	$app->{'web'.$_REQUEST['page']}();
} else { 
	$app->webLogin();
}
?>