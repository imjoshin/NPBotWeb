<?php
//error_reporting(0); // Disable all errors.

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
		$open_games = (isset($player['open_games']) ? $player['open_games'] : array());
		$complete_games = (isset($player['complete_games']) ? $player['complete_games'] : array());

		$ret = array(
			"open_games" => array(),
			"complete_games" => array(),
		);

		foreach ($open_games as $game)
		{
			$ret["open_games"][] = self::getGame($game['number'], $game, true);
		}

		foreach ($complete_games as $game)
		{
			$ret["complete_games"][] = self::getGame($game['number'], $game, true);
		}

		return $ret;
	}

	private static function getGame($game_id, $game = null,  $check_permission = false)
	{
		$game_config = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($game_id));

		if (isset($game))
		{
			$new_game = array("name" => $game['name']);
		}

		$players = dbQuery("SELECT id, name, nickname FROM player WHERE game_id = ?", array($game_id));

		// if current user is owner of game or admin
		if (!$check_permission || $game['config']['adminUserId'] == $_SESSION['player_id'] || $_SESSION['admin'])
		{
			$new_game['fields'] = count($game_config) ? $game_config[0] : array('game_id' => $game_id);
			if (count($players))
			{
				$new_game['fields']['players'] = $players;
			}

			unset($new_game['fields']['player_id']);
		}

		return $new_game;
	}

	public static function saveSettings($form)
	{
		$settings = dbQuery("SELECT * FROM notification_settings WHERE game_id = ?", array($form['game_id']));
		$new_settings = count($settings) === 0;

		// check if player has access to modify this
		if (!$new_settings && $settings[0]['player_id'] != $_SESSION['player_id'] && !$_SESSION['admin'])
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

		if (strpos($form['webhook_url'], 'hooks.slack.com/services') !== false && (!isset($form['webhook_channel']) || strlen(trim($form['webhook_channel'])) < 7))
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook channel ID."
			));
		}

		if (!isset($form['webhook_name']) || strlen(trim($form['webhook_name'])) < 1)
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook name."
			));
		}

		if (!isset($form['webhook_image']) || strlen(trim($form['webhook_image'])) < 1)
		{
			return array('success'=>false, 'output'=>array(
				"message"=>"Invalid webhook image."
			));
		}

		// set player nicknames
		if (isset($form['players']))
		{
			foreach($form['players'] as $id => $nickname)
			{
				if (trim($nickname) != "")
				{
					dbQuery("UPDATE player SET nickname = ? WHERE game_id = ? AND id = ?", array($nickname, $form['game_id'], $id));
				}
			}
		}

		$print_leaderboard = (int) (isset($form['print_leaderboard']) && $form['print_leaderboard'] == 'on');
		$print_turns_taken = (int) (isset($form['print_turns_taken']) && $form['print_turns_taken'] == 'on');
		$print_game_over = (int) (isset($form['print_game_over']) && $form['print_game_over'] == 'on');
		$print_last_players = (int) (isset($form['print_last_players']) && $form['print_last_players'] == 'on');
		$print_warning = (int) (isset($form['print_warning']) && $form['print_warning'] == 'on');

		if ($new_settings)
		{
			$fields = "game_id, player_id, user_id, print_turn_start_format, " .
					  "print_leaderboard, print_leaderboard_format, " .
		  			  "print_turns_taken, print_turns_taken_format, " .
		  			  "print_game_over, print_game_over_format, " .
					  "print_last_players, print_last_players_n, print_last_players_format, " .
					  "print_warning, print_warning_n, print_warning_format, " .
		  			  "webhook_name, webhook_url, webhook_image, webhook_channel";
			$result = dbQuery(
				"INSERT INTO notification_settings($fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
				array(
					$form['game_id'], $_SESSION['player_id'], $_SESSION['id'], trim($form['print_turn_start_format']),
					$print_leaderboard, trim($form['print_leaderboard_format']),
					$print_turns_taken, trim($form['print_turns_taken_format']),
					$print_game_over, trim($form['print_game_over_format']),
					$print_last_players, $form['print_last_players_n'], trim($form['print_last_players_format']),
					$print_warning, $form['print_warning_n'], trim($form['print_warning_format']),
					trim($form['webhook_name']), trim($form['webhook_url']), trim($form['webhook_image']), trim($form['webhook_channel'])
				)
			);
		}
		else
		{
			$fields = "print_turn_start_format = ?, " .
					  "print_leaderboard = ?, print_leaderboard_format = ?, " .
		  			  "print_turns_taken = ?, print_turns_taken_format = ?, " .
		  			  "print_game_over = ?, print_game_over_format = ?, " .
					  "print_last_players = ?, print_last_players_n = ?, print_last_players_format = ?, " .
					  "print_warning = ?, print_warning_n = ?, print_warning_format = ?, " .
		  			  "webhook_name = ?, webhook_url = ?, webhook_image = ?, webhook_channel = ?";
			$result = dbQuery(
				"UPDATE notification_settings SET $fields WHERE game_id = ?",
				array(
					trim($form['print_turn_start_format']),
					$print_leaderboard, trim($form['print_leaderboard_format']),
					$print_turns_taken, trim($form['print_turns_taken_format']),
					$print_game_over, trim($form['print_game_over_format']),
					$print_last_players, $form['print_last_players_n'], trim($form['print_last_players_format']),
					$print_warning, $form['print_warning_n'], trim($form['print_warning_format']),
					trim($form['webhook_name']), trim($form['webhook_url']), trim($form['webhook_image']), trim($form['webhook_channel']),
					$form['game_id']
				)
			);

			if (!$_SESSION['admin'])
			{
				// update player_id in case someone that is not the admin set this up
				// also this is super hacky
				dbQuery(
					"UPDATE notification_settings SET player_id = ?, user_id = ? WHERE game_id = ?",
					array(
						$_SESSION['player_id'],
						$_SESSION['id'],
						$form['game_id']
					)
				);
			}
		}

		$game_data = self::getGame($form['game_id']);
		return array('success'=>true, 'output'=>$game_data['fields']);
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

		$client = User::getGameClient($settings[0]['user_id']);

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
		$open_games = (isset($player['open_games']) ? $player['open_games'] : array());
		$complete_games = (isset($player['complete_games']) ? $player['complete_games'] : array());
		foreach (array_merge($open_games, $complete_games) as $g)
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
				if (is_numeric($var))
				{
					return self::getTurn($client, $universe, $var, $game_settings);
				}
				else
				{
					return ['error' => 'Unknown parameter.'];
				}
		}

	}

	public static function getLatestTurn($client, $universe, $game_settings)
	{
		// logic for player data from https://github.com/BrandonDusseau/np2-wallboard/blob/master/game.php
		// Modify player information to remove private data and add attributes
		if (!empty($universe['players']))
		{
			$universe['turn_jump_ticks'] = $game_settings['turn_jump_ticks'];
			$universe['turn_num'] = $universe['started'] ? ($universe['tick'] / $universe['turn_jump_ticks']) + 1 : 0;

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
				self::renameArrayKey($player, 'total_fleets', 'total_carriers');
				self::renameArrayKey($player, 'total_strength', 'total_ships');
				self::renameArrayKey($player, 'conceded', 'status');

				foreach ($player['tech'] as &$tech)
				{
					$tech_strip = ['sv', 'research', 'bv', 'brr'];
					$tech = array_diff_key($tech, array_flip($tech_strip));
				}

				// Add player color and shape
				$player['color'] = $player_colors[$player['id'] % 8];
				$player['shape'] = floor($player['id'] / 8);
				$rank[] = ['player' => $player['id'], 'stars' => $player['total_stars'], 'ships' => $player['total_ships']];

				if ($universe['turn_num'] > 1)
				{
					$last_turn = dbQuery(
						"SELECT * FROM player_turn WHERE player_id = ? AND turn_id = ? AND game_id = ?",
						array($player['id'], $universe['turn_num'] - 1, $game_settings['id'])
					);
					if (count($last_turn) > 0)
					{
						$player['rank_last'] = $last_turn[0]['rank'];
					}
				}

				$players_rekeyed[$player['id']] = $player;
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

		// if this is a dark galaxy, hide carriers and stars
		if ($game_settings['dark_galaxy'] == 1)
		{
			unset($universe['carriers']);
			unset($universe['stars']);
		}
		else
		{
			// hide carriers if game is still running
			if (!$universe['game_over'])
			{
				unset($universe['carriers']);
			} else {
				// reformat carriers and stars
				foreach ($universe['carriers'] as &$carrier)
				{
					self::renameArrayKey($carrier, 'uid', 'id');
					self::renameArrayKey($carrier, 'n', 'name');
					self::renameArrayKey($carrier, 'puid', 'player_id');
					self::renameArrayKey($carrier, 'st', 'ship_count');
					self::renameArrayKey($carrier, 'o', 'waypoints');
					self::renameArrayKey($carrier, 'l', 'loop');

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

				// hide certain data if game is still running
				if (!$universe['game_over'])
				{
					unset($star['ship_count']);
					unset($star['economy']);
					unset($star['industry']);
					unset($star['science']);
					unset($star['has_gate']);
				}
			}
		}

		foreach ($universe['players'] as &$player)
		{
			ksort($player);
			ksort($player['tech']);
		}

		// set/unset extra fields
		unset($universe['now']);
		unset($universe['trade_cost']);
		unset($universe['trade_scanned']);
		unset($universe['player_uid']);
		unset($universe['start_time']);
		self::renameArrayKey($universe, 'fleet_speed', 'carrier_speed');
		self::renameArrayKey($universe, 'turn_based_time_out', 'turn_end');
		$universe['game_id'] = $game_settings['id'];

		if ($universe['started'])
		{
			$universe['turn_end'] = intval($universe['turn_end'] / 1000);
			$universe['turn_start'] = intval($universe['turn_end'] - ($game_settings['turn_time'] * 60 * 60));
		}

		ksort($universe);
		return $universe;
	}

	public static function getAllTurns($client, $universe, $game_settings)
	{
		$current_turn = self::getLatestTurn($client, $universe, $game_settings);
		$turns = [];

		for ($i = 1; $i < $current_turn['turn_num']; $i++)
		{
			$turn = self::getTurn($client, $universe, $i, $game_settings, false);
			if (!array_key_exists('error', $turn))
			{
				$turns[] = $turn;
			}
		}

		return ['settings' => $game_settings, 'turns' => $turns];
	}

	public static function getTurn($client, $universe, $turn, $game_settings, $turn_only = true)
	{
		$turns = dbQuery("SELECT * FROM game_turn WHERE game_id = ? AND id = ?", array($game_settings['id'], $turn));
		if (!count($turns))
		{
			return ['error' => 'Invalid turn.'];
		}

		$turn_data = $turns[0];

		$players = dbQuery("
			SELECT player.name, player.color, player.avatar, player.shape, player_turn.* FROM np.player_turn
			JOIN np.player ON player.id = player_turn.player_id and player.game_id = player_turn.game_id
			WHERE player_turn.game_id = ? AND player_turn.turn_id = ?
			",
			array($game_settings['id'], $turn)
		);

		if (!count($players))
		{
			return ['error' => 'Failed to get players.'];
		}

		foreach ($players as &$player)
		{
			$player['tech'] = json_decode($player['tech']);
			$player['taken_at'] = strtotime($player['taken_at']);
			unset($player['turn_id']);
			unset($player['game_id']);
			self::renameArrayKey($player, 'player_id', 'id');
			ksort($player);
		}

		$turn_data['players'] = $players;
		$turn_data['turn_end'] = strtotime($turn_data['turn_end']);
		$turn_data['turn_start'] = strtotime($turn_data['turn_start']);

		if (array_key_exists('stars', $turn_data))
		{
			$turn_data['stars'] = json_decode($turn_data['stars']);
		}

		if (array_key_exists('carriers', $turn_data))
		{
			$turn_data['carriers'] = json_decode($turn_data['carriers']);
		}

		self::renameArrayKey($turn_data, 'id', 'turn_num');

		if ($turn_only)
		{
			$game_data = dbQuery("SELECT id, name, description, start_time, game_over, settings from game where id = ?;", array($game_settings['id']));

			if (!count($game_data))
			{
				return ['error' => 'Failed to get game data.'];
			}

			$game_data = $game_data[0];

			$game_data['start_time'] = strtotime($game_data['start_time']);
			unset($game_data['settings']);
			$turn_data = array_merge($turn_data, $game_data);
		}
		else
		{
			unset($turn_data['game_id']);
		}

		unset($turn_data['notified']);
		unset($turn_data['notified_players']);
		ksort($turn_data);
		return $turn_data;
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
		$game_settings['name'] = $universe['name'];
		self::renameArrayKey($game_settings, 'production_ticks', 'production_rate');
		self::renameArrayKey($game_settings, 'number', 'id');
		unset($game_settings['status']);
		unset($game_settings['creator']);

		ksort($game_settings);
		return $game_settings;
	}

	private static function renameArrayKey(&$array, $old_key, $new_key)
	{
		if ($old_key !== $new_key && array_key_exists($old_key, $array))
		{
			$array[$new_key] = $array[$old_key];
			unset($array[$old_key]);
		}
	}
}
