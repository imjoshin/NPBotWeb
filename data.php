<?php
header('Content-Type: application/json');
require 'php/constants.php';
require_once(BASE_PATH . 'utils.php');
require_once(BASE_PATH . 'functions/game.php');

if (!isset($_GET['game_id']))
{
	echo json_encode(array("error" => "No game ID given."));
}

echo json_encode(Game::getGameInfo($_GET['game_id'], isset($_GET['v']) ? $_GET['v'] : 'all'));
?>
