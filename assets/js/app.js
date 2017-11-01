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

		$("#settings").trigger('reset');
		$('#leaderboard-field').show();
		$(this).attr('data-type', 'discord');
		$('#slack-channel').hide();
	});

	$('.btn-game').on('click', function() {
		if ($(this).attr('data-fields') == null) {
			alert("You are not the admin of this game.\nPlease contact the admin to set up notifications.");
			return;
		}

		$('.view').hide();
		$("#settings-name").text($(this).html());

		// Set fields on form
		var fields = $.parseJSON($(this).attr('data-fields'));
		$.each(fields, function(input, value) {
			input = input.replace('[', '\\[').replace(']', '\\]');
			if ($('[name = ' + input + ']').attr("type") == "checkbox") {
				$('[name = ' + input + ']').prop('checked', value == 1);
			} else {
				$('[name = ' + input + ']').val(value);
			}
		});

		// TODO fix hide/show settings like leaderboard and channel id

		$('#gamesettings').show();
	});

	$(document).on('click', '#settings .btn:not(.disabled)', function() {
		var btn = $(this);
		btn.addClass('disabled');

		$.ajax({
			type: "POST",
			dataType: "json",
			url: "php/ajax.php",
			data: {
				call: 'save_settings',
				form: $('#settings').serialize()
			},
			success: function(data) {
				if (data.success) {
					$.each($('.btn-game'), function(k, v) {
						// find game button
						if ($(this).attr('data-fields') && $(this).attr('data-fields').indexOf(data.output['game_id']) >= 0) {
							$(this).attr('data-fields', JSON.stringify(data.output));
						}
					});

					$('.btn-home').trigger('click');
				} else {
					alert(data.output['message']);
				}
			},
			complete: function() {
				btn.removeClass('disabled');
			}
		});
	})
});
