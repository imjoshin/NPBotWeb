<div class='btn btn-home'>
	<i class="fa fa-home"></i>
</div>
<form id="settings">
	<h1 id="settings-name">Spacegame</h1>
	<div class="category">
		<span>Webhook Info</span>
	</div>
	<div class="field">
		<span class='block'>Webhook URL</span>
		<input id='webhook-url' name='webhook_url' type='text' maxlength="256" data-type='discord'>
	</div>
	<div id="slack-channel" class="field" style="display: none;">
		<span class='block'>Webhook Channel ID</span>
		<input id='webhook-channel' name='webhook_channel' type='text' maxlength="32">
	</div>
	<div class="field">
		<span class='block'>Webhook Name</span>
		<input name='webhook_name' type='text' maxlength="32" value="Neptune's Pride">
	</div>
	<div class="field">
		<span class='block'>Webhook Image</span>
		<input name='webhook_image' type='text' maxlength="512" value="http://joshjohnson.io/images/np.png">
	</div>
	<div class="category">
		<span>Notifications</span>
	</div>
	<div class="field">
		<span class='block'>Turn Start</span>
		<input name='print_turn_start_format' type='text' value="Turn *%TURN%* just started! Here is the leaderboard:" maxlength="512">
	</div>
	<div class="field">
		<span>Turn Leaderboard</span>
		<input name='print_leaderboard' type='checkbox' checked="checked">
	</div>
	<div id='print-leaderboard-field' class="field">
		<input name='print_leaderboard_format' type='text' value=":np-star: %STARS% :np-ship: %SHIPS% :np-res: %TECH%\n:np-econ: %ECON% :np-ind: %INDUSTRY% :np-sci: %SCIENCE%" maxlength="512">
	</div>
	<div class="field">
		<span>Player Turns Taken</span>
		<input name='print_turns_taken' type='checkbox'>
	</div>
	<div id='print-turns-taken-field' class="field">
		<input name='print_turns_taken_format' type='text' value="*%PLAYER%* just took their turn!" maxlength="512">
	</div>
	<div class="field">
		<span>Last Players Warning</span>
		<input name='print_last_players' type='checkbox' checked="checked">
		<input name='print_last_players_n' type='number' value="1" min="1" max="32">
	</div>
	<div id='print-last-players-field' class="field">
		<input name='print_last_players_format' type='text' value="There are *%COUNT%* players left to taken their turn\n%PLAYERS%" maxlength="512">
	</div>
	<div class="field">
		<span>Turn End Warning (hrs)</span>
		<input name='print_warning' type='checkbox' checked="checked">
		<input name='print_warning_n' type='number' value="2" min="1" max="23">
	</div>
	<div id='print-warning-field' class="field">
		<input name='print_warning_format' type='text' value="There are only *%HOURS%* hours left to take your turn!" maxlength="512">
	</div>
	<div class="category">
		<span>Nicknames</span>
	</div>
	<div class="field" id='no-players-yet'>
		<span>Players have not been added yet. Please hold tight and wait for the first game scan to complete!</span>
	</div>
	<table id='player-table'>
		<tr class='template'>
			<td>
			</td>
			<td>
				<input type='text' maxlength="32">
			</td>
		</tr>
	</table>
	<input type='hidden' name='game_id'></input>
	<div class="btn btn-red btn-block btn-center">Save</div>
</form>
