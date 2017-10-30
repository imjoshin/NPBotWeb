<?php

require_once(BASE_PATH . "triton/client.php");
require_once(BASE_PATH . 'functions/user.php');

class Game
{
	public static function getGameList()
	{
		ob_start();
		$client = User::getGameClient();
		if (!$client)
		{
			return array();
		}

		$server = $client->GetServer();
		if (!$server)
		{
			return array();
		}

		$player = $server->GetPlayer();
		if (!$player)
		{
			return array();
		}

		// get list of games
		$games = (isset($player['open_games']) ? $player['open_games'] : array());
		$ret = array();
		foreach ($games as $game)
		{
			$game_config = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($game['number']));

			$ret[] = array(
				"name" => $game['name'],
				"fields" => count($game_config) ? $game_config[0] : array()
			);
		}

		return $ret;
	}
}
