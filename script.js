(function($) {

	function balitsa_success(data) {
		const node = this;
		if (typeof(data) !== 'object')
			return alert(data);
		node.closest('.balitsa-container').replaceWith(data.html);
	}

	$(document).on('click', '.balitsa-link', function(event) {
		event.preventDefault();
		const link = $(this);
		const container = link.closest('.balitsa-container');
		if (container.data('balitsa-busy') === 'on')
			return false;
		const spinner = container.find('.balitsa-spinner');
		if (link.data('balitsa-confirm') !== undefined && !confirm(link.data('balitsa-confirm')))
			return false;
		container.data('balitsa-busy', 'on');
		spinner.toggleClass(spinner.data('balitsa-spinner-toggle'));
		const data = {};
		if (link.hasClass('balitsa-submit')) {
			link.closest('.balitsa-form').find('.balitsa-field').each(function() {
				const field = $(this);
				const name = field.data('balitsa-name');
				data[name] = field.val();
			});
		}
		$.post(link.prop('href'), data).done(function(data) {
			balitsa_success.call(link, data);
		}).fail(function(jqXHR) {
			alert(jqXHR.statusText + ' ' + jqXHR.status);
		}).always(function() {
			spinner.toggleClass(spinner.data('balitsa-spinner-toggle'));
			container.data('balitsa-busy', 'off');
		});
	});

	$(document).on('click', '.balitsa-insert', function(event) {
		event.preventDefault();
		const link = $(this);
		const container = link.closest('.balitsa-container');
		const form = container.find(link.data('balitsa-form'));
		form.find('.balitsa-field').each(function() {
			const field = $(this);
			const name = field.data('balitsa-name');
			const value = link.data('balitsa-field-' + name);
			field.val(value);
		});
		form.find('.balitsa-submit').prop('href', link.prop('href'));
		form.show();
	});

	$(document).on('click', '.balitsa-cancel', function(event) {
		event.preventDefault();
		const link = $(this);
		const form = link.closest('.balitsa-form');
		form.hide();
		form.find('.balitsa-field').each(function() {
			const field = $(this);
			field.val('');
		});
		form.find('.balitsa-submit').prop('href', '');
	});

})(jQuery);
