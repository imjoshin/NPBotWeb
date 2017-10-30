$(document).foundation()

$().ready(function() {
	$('.btn-home').on('click', function() {
		$('.view').hide();
		$('#gamelist').show();
	});
	
	$('.btn-game').on('click', function() {
		$('.view').hide();
		$('#gamesettings').show();
	});
});
