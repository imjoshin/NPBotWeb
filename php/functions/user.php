<?php

require "triton/client.php";

class User
{
	public static function login($username, $password)
	{
		$username = strtolower($username);
		if (isset($_SESSION['id']))
		{
			return array('success'=>true, 'output'=>array(
				"message"=>"Already logged in."
			));
		}

		$user = dbQuery("SELECT id, username, password, ltime FROM user WHERE username = ?", array($username));
		$new_user = count($user) === 0;

		// user has logged in before and the passwords match and we want to load from cache
		if (!$new_user && $password === $user[0]['password'] && loadFromCache($user[0]['id']))
		{
			session_start();
			$_SESSION['id'] = $user[0]['id'];
			$_SESSION['username'] = $username;

			return array('success'=>true);
		}

		// verify with triton if new user or new password
		$client = new TritonClient($username, $password);
		ob_start();
		$auth = $client->authenticate();
		ob_clean();

		if (!$auth)
		{
			return array('success'=>false, 'output'=>array(
			 	"message"=>"Invalid alias/email and password."
			));
		}

		if ($new_user)
		{
			// create new user
			$result = dbQuery("INSERT INTO user(username, password, utime, ltime) VALUES (?, ?, NOW(), NOW())", array($username, $password));
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
				$result = dbQuery("UPDATE user SET ltime = NOW() WHERE id = ?", array($user[0]['id']));
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
				$result = dbQuery("UPDATE user SET password = ?, utime = NOW(), ltime = NOW() WHERE id = ?", array($password, $user[0]['id']));
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
