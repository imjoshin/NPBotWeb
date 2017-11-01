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
	public static function getGameInfo($game_id, $var = 'all')
	{
		ob_start();
		$settings = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($game_id));
		if (count($settings) == 0)
		{
			return array("error" => "Game ID has not been set up.");
		}

		$client = User::getGameClient($settings[0]['owner_user_id']);

		$server = $client->GetServer();
		if (!$server)
		{
			return array("error" => "Failed to fetch server data.");
		}

		$player = $server->GetPlayer();
		if (!$player)
		{
			return array("error" => "Failed to fetch game data.");
		}

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

		// get list of games to find game settings
		$games = (isset($player['open_games']) ? $player['open_games'] : array());
		foreach ($games as $g)
		{
			if ($g['number'] == $game_id)
			{
				$game_settings = $g;
				break;
			}
		}

		$game_settings = self::getSettings($client, $universe, $game_settings);

		switch ($var)
		{
			case 'latest':
				return self::getLatestTurn($client, $universe, $game_settings);
			case 'all':
				return self::getAllTurns($client, $universe, $game_settings);
			case 'settings':
				return $game_settings;
			default:
				return self::getTurn($client, $universe, $turn, $game_settings);
		}

	}

	public static function getLatestTurn($client, $universe, $game_settings)
	{
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
				self::renameArrayKey($player, 'uid', 'id');
				self::renameArrayKey($player, 'alias', 'name');

				foreach ($player['tech'] as &$tech)
				{
					$tech_strip = ['sv', 'research', 'bv', 'brr'];
					$tech = array_diff_key($tech, array_flip($tech_strip));
				}

				// Add player color and shape
				$player['color'] = $player_colors[$player['id'] % 8];
				$player['shape'] = $player['id'] % 8;
				$players_rekeyed[$player['id']] = $player;
				$rank[] = ['player' => $player['id'], 'stars' => $player['total_stars'], 'ships' => $player['total_strength']];
			}

			// Rank the players by stars, ships, then id.
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
					// Otherwise, everything is equal and we should just sort by id
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

		self::renameArrayKey($universe, 'fleets', 'carriers');

		// TODO detect if this is a dark game. If so, hide carriers and stars
		foreach ($universe['carriers'] as &$carrier)
		{
			self::renameArrayKey($carrier, 'uid', 'id');
			self::renameArrayKey($carrier, 'n', 'name');
			self::renameArrayKey($carrier, 'puid', 'player_id');
			self::renameArrayKey($carrier, 'st', 'ship_count');
			self::renameArrayKey($carrier, 'o', 'waypoints');

			foreach ($carrier['waypoints'] as &$waypoint)
			{
				$newWaypoint['star_id'] = $waypoint[1];
				$newWaypoint['ship_count'] = $waypoint[3];

				// find action name
				switch ($waypoint[2])
				{
					case 0:
						$newWaypoint['action'] = 'do_nothing';
						unset($newWaypoint['ship_count']);
						break;
					case 1:
						$newWaypoint['action'] = 'collect_all';
						unset($newWaypoint['ship_count']);
						break;
					case 2:
						$newWaypoint['action'] = 'drop_all';
						unset($newWaypoint['ship_count']);
						break;
					case 3:
						$newWaypoint['action'] = 'collect';
						break;
					case 4:
						$newWaypoint['action'] = 'drop';
						break;
					case 5:
						$newWaypoint['action'] = 'collect_all_but';
						break;
					case 6:
						$newWaypoint['action'] = 'garrison_star';
						break;
					default:
						$newWaypoint['action'] = 'do_nothing';
						break;
				}

				$waypoint = $newWaypoint;
			}
		}

		foreach ($universe['stars'] as &$star)
		{
			self::renameArrayKey($star, 'uid', 'id');
			self::renameArrayKey($star, 'n', 'name');
			self::renameArrayKey($star, 'puid', 'player_id');
			self::renameArrayKey($star, 'st', 'ship_count');
			self::renameArrayKey($star, 'e', 'economy');
			self::renameArrayKey($star, 'i', 'industry');
			self::renameArrayKey($star, 's', 'science');
			self::renameArrayKey($star, 'nr', 'natural_resources');
			self::renameArrayKey($star, 'r', 'radius');
			self::renameArrayKey($star, 'ga', 'has_gate');
		}

		// set/unset extra fields
		unset($universe['now']);
		unset($universe['trade_cost']);
		unset($universe['trade_scanned']);
		unset($universe['player_uid']);
		self::renameArrayKey($universe, 'fleet_speed', 'carrier_speed');
		$universe['turn_jump_ticks'] = $game_settings['turn_jump_ticks'];
		$universe['turn_num'] = ($universe['tick'] / $universe['turn_jump_ticks']) + ($universe['production_counter'] / $universe['turn_jump_ticks']);

		ksort($universe);
		return $universe;
	}

	public static function getAllTurns($client, $universe, $game_settings)
	{
		return array("message" => "Getting all turns.");
	}

	public static function getTurn($client, $universe, $turn, $game_settings)
	{
		return array("message" => "Getting turn $turn.");
	}

	public static function getSettings($client, $universe, $game_settings)
	{
		$game_settings = array_merge($game_settings, $game_settings['config']);
		unset($game_settings['config']);

		// rename keys to match standard
		foreach ($game_settings as $old_key => $value)
		{
			$new_key = strtolower(preg_replace('/([A-Z]+)/', '_$1', $old_key));
			self::renameArrayKey($game_settings, $old_key, $new_key);
		}

		$game_settings['stars_for_victory'] = $universe['stars_for_victory'];
		$game_settings['start_time'] = $universe['start_time'];
		$game_settings['total_stars'] = $universe['total_stars'];
		$game_settings['carrier_speed'] = $universe['fleet_speed'];
		self::renameArrayKey($game_settings, 'production_ticks', 'production_rate');

		ksort($game_settings);
		return $game_settings;
	}

	private static function renameArrayKey(&$array, $old_key, $new_key)
	{
		if (array_key_exists($old_key, $array))
		{
			$array[$new_key] = $array[$old_key];
			unset($array[$old_key]);
		}
	}
}
