<?php

require_once(BASE_PATH . "triton/client.php");

class User
{
	public static function getGameClient()
	{
		ob_start();
		$user = dbQuery("SELECT * FROM user WHERE id = ?", array($_SESSION['id']));
		$client = new TritonClient($user[0]['username'], $user[0]['password']);

		if ($client->logged_in)
		{
			return $client;
		}

		if (loadFromCache($_SESSION['id']))
		{
			$client->auth_cookie = $user[0]['cookie'];
			$client->logged_in = true;
			return $client;
		}

		// authenticate with triton
		$auth = $client->authenticate();
		ob_end_clean();

		if ($auth)
		{
			$result = dbQuery("UPDATE user SET ltime = NOW(), cookie = ? WHERE id = ?", array($client->auth_cookie, $_SESSION['id']));
			if ($result === false)
			{
				// TODO detect error correctly
				// return array('success'=>false, 'output'=>array(
				//  	"message"=>"Failed to create user."
				// ));
			}
		}

		return null;
	}

	public static function login($username, $password)
	{
		ob_start();
		$username = strtolower($username);
		if (isset($_SESSION['id']))
		{
			return array('success'=>true, 'output'=>array(
				"message"=>"Already logged in."
			));
		}

		$user = dbQuery("SELECT id, username, password, ltime FROM user WHERE username = ?", array($username));
		$new_user = count($user) === 0;

		// create triton client, don't auth yet
		$client = new TritonClient($username, $password);

		// user has logged in before and the passwords match and we want to load from cache
		// or we're already logged in
		if (
			(!$new_user && $password === $user[0]['password'] && loadFromCache($user[0]['id'])) ||
			$client->logged_in
		)
		{
			session_start();
			$_SESSION['id'] = $user[0]['id'];
			$_SESSION['username'] = $username;

			return array('success'=>true);
		}

		// authenticate with triton
		$auth = $client->authenticate();
		ob_end_clean();

		if (!$auth)
		{
			return array('success'=>false, 'output'=>array(
			 	"message"=>"Invalid alias/email and password."
			));
		}

		if ($new_user)
		{
			// create new user
			$result = dbQuery("INSERT INTO user(username, password, utime, ltime, cookie) VALUES (?, ?, NOW(), NOW(), ?)", array($username, $password, $client->auth_cookie));
			if ($result === false)
			{
				// TODO detect error correctly
				// return array('success'=>false, 'output'=>array(
				//  	"message"=>"Failed to create user."
				// ));
			}

			$user = dbQuery("SELECT id FROM user WHERE username = ?", array($username));
		}
		else
		{
			if ($password === $user[0]['password'])
			{
				// only update ltime
				$result = dbQuery("UPDATE user SET ltime = NOW(), cookie = ? WHERE id = ?", array($client->auth_cookie, $user[0]['id']));
				if ($result === false)
				{
					// TODO detect error correctly
					// return array('success'=>false, 'output'=>array(
					//  	"message"=>"Failed to make db call."
					// ));
				}
			}
			else
			{
				// update password and utime since a new password was given
				$result = dbQuery("UPDATE user SET password = ?, utime = NOW(), ltime = NOW(), cookie = ? WHERE id = ?", array($password, $client->auth_cookie, $user[0]['id']));
				if ($result === false)
				{
					// TODO detect error correctly
					// return array('success'=>false, 'output'=>array(
					//  	"message"=>"Failed to make db call."
					// ));
				}
			}
		}

		session_start();

		$_SESSION['id'] = $user[0]['id'];
		$_SESSION['username'] = $username;

		return array('success'=>true);
	}

	public static function logout()
	{
		session_start();
		session_unset();
		session_destroy();
		return array('success'=>true);
	}

	public static function getGames()
	{

	}
}

?>
