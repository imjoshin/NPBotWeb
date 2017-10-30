<h1>Gamelist</h1>
<?php
foreach ($game_list as $game)
{
	?>
		<div class='btn btn-block btn-game' data-fields="<?php echo json_encode($game['fields']); ?>">
			<?php echo $game['name']; ?>
		</div>
	<?php
}
?>
