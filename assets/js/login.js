$().ready(function() {
	$(document).on("click", "#login .btn:not(.disabled)", function() {
		var form = $(this).parent();
		var btn = $(this);

		btn.addClass('disabled');
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "php/ajax.php",
			data: {
				call: 'login',
				form: form.serialize()
			},
			success: function(data) {
				if (data.success) {
					location.reload();
				} else {
					alert(data.output['message']);
				}
			},
			complete: function() {
				btn.removeClass('disabled');
			}
		});
	});

	$(".btn-logout").on("click", function() {
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "php/ajax.php",
			data: {
				call: 'logout'
			},
			success: function(data) {
				if (data.success) {
					location.reload();
				} else {
					alert(data.output['message']);
				}
			}
		});
	});

	$('#login input').on('keypress', function (e) {
		if (e.which == 13) {
			$('#login .btn').click();
		}
	});
});
