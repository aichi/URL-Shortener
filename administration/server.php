<?php
require_once '../config.php';

require_once './lib/TObject.php';
require_once './lib/TObjectStatic.php';
require_once './lib/Application.php';
require_once './lib/ILogin.php';
require_once './lib/IPersistence.php';


$app = Application::getInstance($CONFIG);

if (!isset($_REQUEST['page'])) {
	$_REQUEST['page'] = 'index';
}

if ($app->isUserLogged() && in_array($_REQUEST['page'], $app->allowedPages() ) ) {
	$app->{'render'.$_REQUEST['page']}();
} else {
	$app->renderLogin();
}
?>