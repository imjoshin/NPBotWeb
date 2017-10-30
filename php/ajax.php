<?php

require_once('constants.php');
require_once(BASE_PATH . 'utils.php');
require_once(BASE_PATH . 'functions/user.php');

if (!isset($_POST['call']))
{
	return;
}

session_start();

if (!isset($_SESSION['id']) && !in_array(strtolower($_POST['call']), array("login", "logout")))
{
	$ret = array("success"=>false, "output"=>array(
		"message"=>"Session expired. Please refresh."
	));
	return json_encode($ret);
}

// look for serialized form
if (isset($_POST['form']))
{
	$_POST = array_merge($_POST, unserializeForm($_POST['form']));
	unset($_POST['form']);
}

switch(strtolower($_POST['call']))
{
	case 'login':
		$ret = User::login($_POST['username'], $_POST['password']);
		break;
	case 'logout':
		$ret = User::logout();
		break;
	case 'save_settings':
		$ret = Game::saveSettings($_POST);
		break;
}

echo json_encode($ret);

?>
