$(document).foundation();

$().ready(function () {
	// show channel field if slack webhook
	$("#webhook-url").on('keyup', function () {
		if ($(this).attr('data-type') == "discord") {
			if ($(this).val().indexOf('hooks.slack.com/services') >= 0) {
				$(this).attr('data-type', 'slack');

				if ($('#gamesettings').is(":visible")) {
					$('#slack-channel').slideDown(150);
				} else {
					$('#slack-channel').show();
				}
			}
		} else if ($(this).attr('data-type') == "slack") {
			if ($(this).val().indexOf('discord.com/api/webhooks') >= 0) {
				$(this).attr('data-type', 'discord');

				if ($('#gamesettings').is(":visible")) {
					$('#slack-channel').slideUp(150);
				} else {
					$('#slack-channel').hide();
				}
			}
		}
	});

	$('#settings input[type="checkbox"]').on('change', function () {
		var field = $("#" + $(this).attr('name').replace(/\_/g, '-') + '-field');

		if ($(this).is(':checked')) {
			if ($('#gamesettings').is(":visible")) {
				field.slideDown(150);
			} else {
				field.show();
			}

			$(this).siblings("input").prop('disabled', false);
		} else {
			if ($('#gamesettings').is(":visible")) {
				field.slideUp(150);
			} else {
				field.hide();
			}

			$(this).siblings("input").prop('disabled', true);
		}
	});

	$('.btn-home').on('click', function () {
		$('.view').hide();
		$('#home').show();

		$("#settings").trigger('reset');
		$('#leaderboard-field').show();
		$('#webhook-url').attr('data-type', 'discord');
		$('#slack-channel').hide();
		$('#no-players-yet').show();
		$('#player-table tr:not(.template)').remove();
	});

	$('.btn-game').on('click', function () {
		if ($(this).attr('data-fields') == null) {
			alert("You are not the admin of this game.\nPlease contact the admin to set up notifications.");
			return;
		}

		$('.view').hide();
		$("#settings-name").text($(this).html());

		// Set fields on form
		var fields = $.parseJSON($(this).attr('data-fields'));
		$.each(fields, function (input, value) {
			input = input.replace('[', '\\[').replace(']', '\\]');
			if ($('[name = ' + input + ']').attr("type") == "checkbox") {
				$('[name = ' + input + ']').prop('checked', value == 1);
			} else {
				$('[name = ' + input + ']').val(value);
			}
		});

		if ('players' in fields) {
			$('#no-players-yet').hide();
			$.each(fields['players'], function (key, player) {
				var row = $('#player-table .template').clone();
				row.removeClass('template');
				row.find('td:first-child').text(player['name']);
				row.find('input').val(player['nickname']);
				row.find('input').attr('name', 'players[' + player['id'] + ']');
				row.attr('data-id', player['id']);
				$('#player-table').append(row);
			});
		}

		$("#webhook-url").trigger('keyup');
		$('#settings input[type="checkbox"]').trigger('change');

		$('#gamesettings').show();
	});

	$(document).on('click', '#settings div.btn:not(.disabled)', function () {
		var btn = $(this);
		btn.addClass('disabled');
		$('#settings input[type="number"]').prop('disabled', false);

		$.ajax({
			type: "POST",
			dataType: "json",
			url: "php/ajax.php",
			data: {
				call: 'save_settings',
				form: $('#settings').serialize()
			},
			success: function (data) {
				if (data.success) {
					$.each($('.btn-game'), function (k, v) {
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
			complete: function () {
				btn.removeClass('disabled');
			}
		});
	});
});
