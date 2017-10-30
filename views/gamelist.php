<h1>Gamelist</h1>
<?php
foreach ($game_list as $game)
{
	?>
		<div class='btn btn-block btn-game' <?php echo isset($game['fields']) ? "data-fields='" . json_encode($game['fields']) . "'" : ''; ?>>
			<?php echo $game['name']; ?>
		</div>
	<?php
}
?>
