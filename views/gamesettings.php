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
		<input id='webhook_url' name='webhook_url' type='text' maxlength="256">
	</div>
	<div id="slack-channel" class="field">
		<span class='block'>Webhook Channel ID</span>
		<input id='webhook_channel' name='webhook_channel' type='text' maxlength="32">
	</div>
	<div class="field">
		<span class='block'>Webhook Name</span>
		<input id='webhook_name' name='webhook_name' type='text' maxlength="32" value="Neptune's Pride">
	</div>
	<div class="field">
		<span class='block'>Webhook Image</span>
		<input id='webhook_name' name='webhook_name' type='text' maxlength="512" value="http://joshjohnson.io/images/np.png">
	</div>
	<div class="category">
		<span>Notifications</span>
	</div>
	<div class="field">
		<span>Turn Leaderboard</span>
		<input id='print_leaderboard' name='print_leaderboard' type='checkbox' checked="checked">
	</div>
	<div class="field">
		<span class='block'>Leaderboard Format</span>
		<input id='leaderboard_format' name='leaderboard_format' type='text' value=":np-star: %STARS% :np-ship: %SHIPS% :np-res: %TECH%\n:np-econ: %ECON% :np-ind: %INDUSTRY% :np-sci: %SCIENCE%" maxlength="512">
	</div>
	<div class="field">
		<span>Turns Taken</span>
		<input id='print_turns_taken' name='print_turns_taken' type='checkbox'>
	</div>
	<div class="field">
		<span>N Last Players</span>
		<input id='print_turns_taken' name='print_turns_taken' type='number' value="1" min="0" max="32">
	</div>
	<div class="field">
		<span>Turn End Warning</span>
		<input id='print_turns_taken' name='print_turns_taken' type='number' value="0" min="0" max="23">
	</div>
	<div class="btn btn-red btn-block btn-center">Save</div>
</form>
