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
		<input id='webhook-name' name='webhook_name' type='text' maxlength="32" value="Neptune's Pride">
	</div>
	<div class="field">
		<span class='block'>Webhook Image</span>
		<input id='webhook-image' name='webhook_image' type='text' maxlength="512" value="http://joshjohnson.io/images/np.png">
	</div>
	<div class="category">
		<span>Notifications</span>
	</div>
	<div class="field">
		<span class='block'>Turn Start Format</span>
		<input id='leaderboard-text-format' name='leaderboard_text_format' type='text' value="Turn *%TURN%* just started! Here is the leaderboard:" maxlength="512">
	</div>
	<div class="field">
		<span>Turn Leaderboard</span>
		<input id='print-leaderboard' name='print_leaderboard' type='checkbox' checked="checked">
	</div>
	<div id='leaderboard-field' class="field">
		<span class='block'>Leaderboard Format</span>
		<input id='leaderboard-format' name='leaderboard_format' type='text' value=":np-star: %STARS% :np-ship: %SHIPS% :np-res: %TECH%\n:np-econ: %ECON% :np-ind: %INDUSTRY% :np-sci: %SCIENCE%" maxlength="512">
	</div>
	<div class="field">
		<span>Player Turns Taken</span>
		<input id='print-turns-taken' name='print_turns_taken' type='checkbox'>
	</div>
	<div class="field">
		<span>N Last Players Warning</span>
		<input id='print-n-last-players' name='print_n_last_players' type='number' value="1" min="0" max="32">
	</div>
	<div class="field">
		<span>Turn End Warning (hrs)</span>
		<input id='print-warning' name='print_warning' type='number' value="1" min="0" max="23">
	</div>
	<div class="category">
		<span>Nicknames</span>
	</div>
	<div class="field">
		<span id='no-players-yet'>Players have not been added yet. Please hold tight and wait for the first game scan to complete!</span>
	</div>
	<input type='hidden' name='game_id'></input>
	<div class="btn btn-red btn-block btn-center">Save</div>
</form>
