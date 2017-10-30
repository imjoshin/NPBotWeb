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

			// if no settings have been made or they were made by the logged in user, allow editing
			if (count($game_config) == 0 || $game_config[0]['owner_user_id'] == $_SESSION['id'])
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

	// response is different here because its used as a public endpoint
	public static function getGameInfo($game_id)
	{
		ob_start();
		$settings = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($game_id));
		if (count($settings) == 0)
		{
			return array("error" => "Game ID has not been set up.");
		}

		$client = User::getGameClient($settings[0]['owner_user_id']);

		$game = $client->GetGame($game_id);
		if (!$game)
		{
			return array("error" => "Failed to fetch game data.");
		}

		$universe = $game->getFullUniverse();
		if (!$universe)
		{
			return array("error" => "Failed to fetch universe data.");
		}

		// logic for player data from https://github.com/BrandonDusseau/np2-wallboard/blob/master/game.php
		// Modify player information to remove private data and add attributes
		if (!empty($universe['players']))
		{
			// Define colors for the players. These eight colors are repeated with each set of eight players.
			$player_colors = [
				"#0000FF",
				"#009FDF",
				"#40C000",
				"#FFC000",
				"#DF5F00",
				"#C00000",
				"#C000C0",
				"#6000C0",
			];

			$players_rekeyed = [];

			// This array is used to determine ranking
			$rank = [];

			foreach ($universe['players'] as &$player)
			{
				// Strip private information
				$player_strip = ['researching', 'researching_next', 'war', 'countdown_to_war', 'cash', 'stars_abandoned'];
				$player = array_diff_key($player, array_flip($player_strip));

				// Rename 'alias' to 'name' for consistency.
				$player['name'] = $player['alias'];
				unset($player['alias']);

				foreach ($player['tech'] as &$tech)
				{
					$tech_strip = ['sv', 'research', 'bv', 'brr'];
					$tech = array_diff_key($tech, array_flip($tech_strip));
				}

				// Add player color and shape
				$player['color'] = $player_colors[$player['uid'] % 8];
				$player['shape'] = $player['uid'] % 8;
				$players_rekeyed[$player['uid']] = $player;
				$rank[] = ['player' => $player['uid'], 'stars' => $player['total_stars'], 'ships' => $player['total_strength']];
			}

			// Rank the players by stars, ships, then UID.
			usort(
				$rank,
				function ($a, $b)
				{
					// B ranks higher if A has fewer stars, or if A has fewer ships and stars are equal
					if ($a['stars'] < $b['stars'] || ($a['stars'] == $b['stars'] && $a['ships'] < $b['ships']))
					{
						return 1;
					}
					// A ranks higher if B has fewer stars, or if B has fewer ships and stars are equal
					elseif ($a['stars'] > $b['stars'] || ($a['stars'] == $b['stars'] && $a['ships'] > $b['ships']))
					{
						return -1;
					}
					// Otherwise, everything is equal and we should just sort by UID
					else
					{
						return ($a['player'] - $b['player']);
					}
				}
			);

			// Add the ranks back into the player data
			// Add 1 to the index to make rankings start at 1.
			foreach ($rank as $index => $player_rank)
			{
				$players_rekeyed[$player_rank['player']]['rank'] = $index + 1;
			}

			$universe['players'] = $players_rekeyed;
		}

		unset($universe['stars']);
		unset($universe['fleets']);
		return $universe;
	}
}
