<?php
require 'auth.php';

function init()
{
	return array();
}

function loadFromCache($user_id)
{
	$user = dbQuery("SELECT ltime FROM user WHERE id = ?", array($user_id));

	if (count($user) == 0)
	{
		return false;
	}

	// get last login difference
	$now = new DateTime();
	$last_login = new DateTime($user[0]['ltime']);
	$diff = $now->diff($last_login);

	// add up minutes since last login
	$minutes = ($diff->format('%a') * 1440) + ($diff->format('%h') * 60) + $diff->format('%i');
	return $minutes < CACHE_TIMEOUT;
}

function unserializeForm($array)
{
	$data = array();
	foreach(explode('&', $array) as $value)
	{
		$value1 = explode('=', $value);
		$key = urldecode($value1[0]);
		$value = urldecode($value1[1]);
		if (preg_match('/\[.+\]/', $key, $match))
		{
			$arrayName = str_replace($match[0], '', $key);
			$arrayKey = str_replace(array('[', ']'), '', $match[0]);

			if (!isset($data[$arrayName]))
			{
				$data[$arrayName] = array();
			}

			$data[$arrayName][$arrayKey] = $value;
		}
		else
		{
			$data[$key] = $value;
		}
	}

	return $data;
}

/*
 * Returns a database connection
 */
function dbConnect()
{
	$result = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

	if(!$result)
	{
		throw new Exception('Could not connect to db server');
	}

	return $result;
}

/*
 * Executes a prepared statement against the database
 *
 * @param string $query  The query to Executes
 * @param array  $params An array of parameters to bind
 * @return array Matching rows in an array
 */
function dbQuery($query, $params = null)
{
	$mysqli = dbConnect();
	$stmt = $mysqli->prepare($query);

	if ($params)
	{
		$bind_params = array();
		$types = "";

		foreach ($params as $i=>$param)
		{
			$bind_params[] = &$params[$i];

			if (is_float($param))
			{
				$types .= "d";
			}
			elseif (is_int($param))
			{
				$types .= "i";
			}
			else
			{
				$types .= "s";
			}
		}

		array_unshift($params, $types);

		call_user_func_array(array($stmt, 'bind_param'), $params);
	}

	$stmt->execute();
	$res = $stmt->get_result();
	$result = array();

	if (is_bool($res))
	{
		return $res;
	}

	while ($row = mysqli_fetch_assoc($res))
	{
		$result[] = $row;
	}

	$stmt->close();
	return $result;
}

?>
