(function($) {

	$(document).on('ready', function() {
		const name = $('#nickname').val();
		const bg = $('#balitsa_color option:selected').val();
		const fg = $('#balitsa_color option:selected').data('fg');
		$('#balitsa_color_preview').
			text(name).
			css('color', fg !== undefined ? fg : 'Black' ).
			css('background-color', bg !== '' ? bg : 'White' );
	});

	$(document).on('change', '#nickname', function() {
		const name = $('#nickname').val();
		$('#balitsa_color_preview').text(name);
	});

	$(document).on('change', '#balitsa_color', function() {
		const bg = $('#balitsa_color option:selected').val();
		const fg = $('#balitsa_color option:selected').data('fg');
		$('#balitsa_color_preview').
			css('color', fg !== undefined ? fg : 'Black' ).
			css('background-color', bg !== '' ? bg : 'White' );
	});

})(jQuery);
