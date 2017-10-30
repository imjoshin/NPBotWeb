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

			$new_game = array("name" => $game['name']);

			if ($game_config[0]['owner_user_id'] == $_SESSION['id'])
			{
				unset($game_config[0]['owner_user_id']);
				$new_game['fields'] = count($game_config) ? $game_config[0] : array('game_id' => $game['number']);
			}

			$ret[] = $new_game;
		}

		return $ret;
	}

	public static function saveSettings($form)
	{
		//error_log(json_encode($form));
		$settings = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($form['game_id']));
		$new_settings = count($settings) === 0;

		if (!$new_settings && $settings[0]['owner_user_id'] != $_SESSION['id'])
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"You do not have permissions to change these settings."
			));
		}

		if (!isset($form['webhook_url']) || strpos($form['webhook_url'], 'hooks.slack.com/services') === false && strpos($form['webhook_url'], 'discordapp.com/api/webhooks') === false)
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook URL."
			));
		}

		if (strpos($form['webhook_url'], 'hooks.slack.com/services') !== false && (!isset($form['webhook_channel']) || strlen($form['webhook_channel']) < 7))
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook channel ID."
			));
		}

		if (!isset($form['webhook_name']) || strlen($form['webhook_name']) < 1)
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook name."
			));
		}

		if (!isset($form['webhook_image']) || strlen($form['webhook_image']) < 1)
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook image."
			));
		}

		if ($new_settings)
		{
			$fields = "game_id, owner_user_id, print_leaderboard, print_turns_taken, print_n_last_players, print_warning, " .
					  "leaderboard_format, leaderboard_text_format, webhook_name, webhook_url, webhook_image, webhook_channel";
			$result = dbQuery(
				"INSERT INTO notification_settings($fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array(
					$form['game_id'],
					$_SESSION['id'],
					isset($form['print_leaderboard']) && $form['print_leaderboard'] == 'on',
					isset($form['print_turns_taken']) && $form['print_turns_taken'] == 'on',
					$form['print_n_last_players'],
					$form['print_warning'],
					$form['leaderboard_format'],
					$form['leaderboard_text_format'],
					$form['webhook_name'],
					$form['webhook_url'],
					$form['webhook_image'],
					$form['webhook_channel']
				)
			);
		}
		else
		{
			$fields = "print_leaderboard = ?, print_turns_taken = ?, print_n_last_players = ?, print_warning = ?, leaderboard_format = ?, " .
					  "leaderboard_text_format = ?, webhook_name = ?, webhook_url = ?, webhook_image = ?, webhook_channel = ?";
			$result = dbQuery(
				"UPDATE notification_settings SET $fields WHERE game_id = ?",
				array(
					isset($form['print_leaderboard']) && $form['print_leaderboard'] == 'on',
					isset($form['print_turns_taken']) && $form['print_turns_taken'] == 'on',
					$form['print_n_last_players'],
					$form['print_warning'],
					$form['leaderboard_format'],
					$form['leaderboard_text_format'],
					$form['webhook_name'],
					$form['webhook_url'],
					$form['webhook_image'],
					$form['webhook_channel'],
					$form['game_id'],
				)
			);
		}

		$settings = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($form['game_id']));

		unset($settings[0]['owner_user_id']);
		return array('success'=>true, 'output'=>$settings[0]);
	}
}
