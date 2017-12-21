<?php
if (!empty($game_list['open_games']))
{
	?>
	<h1>Active Games</h1>
	<?php
	foreach ($game_list['open_games'] as $game)
	{
		// account for single quote
		$encoded_fields = str_replace("'", "&#39", json_encode($game['fields']));
		$fields = isset($game['fields']) ? "data-fields='{$encoded_fields}'" : '';
		?>
			<div class='btn btn-block btn-game' <?php echo $fields; ?>>
				<?php echo $game['name']; ?>
			</div>
		<?php
	}
}

if (!empty($game_list['complete_games']))
{
	?>
	<h1>Completed Games</h1>
	<?php
	foreach ($game_list['complete_games'] as $game)
	{
		// account for single quote
		$encoded_fields = str_replace("'", "&#39", json_encode($game['fields']));
		$fields = isset($game['fields']) ? "data-fields='{$encoded_fields}'" : '';
		?>
			<div class='btn btn-block btn-game' <?php echo $fields; ?>>
				<?php echo $game['name']; ?>
			</div>
		<?php
	}
}
?>
