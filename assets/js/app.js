$(document).foundation()

$().ready(function() {
	// show channel field if slack webhook
	$("#webhook-url").on('keyup', function() {
		if ($(this).attr('data-type') == "discord") {
			if ($(this).val().indexOf('hooks.slack.com/services') >= 0) {
				$(this).attr('data-type', 'slack');
				$('#slack-channel').slideDown(150);
			}
		} else if ($(this).attr('data-type') == "slack") {
			if ($(this).val().indexOf('discordapp.com/api/webhooks') >= 0) {
				$(this).attr('data-type', 'discord');
				$('#slack-channel').slideUp(150);
			}
		}
	});

	$('#print-leaderboard').on('change', function() {
		if ($(this).is(':checked')) {
			$('#leaderboard-field').slideDown(150);
		} else {
			$('#leaderboard-field').slideUp(150);
		}
	});

	$('.btn-home').on('click', function() {
		$('.view').hide();
		$('#home').show();
	});

	$('.btn-game').on('click', function() {
		$('.view').hide();
		$('#gamesettings').show();
	});
});
