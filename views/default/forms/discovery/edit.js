import $ from 'jquery';
import 'jquery.form';
import * as lightbox from 'elgg/lightbox';
import * as spinner from 'elgg/spinner';
import * as system_messages from 'elgg/system_messages';

$(document).on('submit', '#colorbox .elgg-form-discovery-edit', function (e) {

	e.preventDefault();
	var $form = $(this);

	$form.ajaxSubmit({
		dataType: 'json',
		data: {
			'X-Requested-With': 'XMLHttpRequest'
		},
		beforeSend: function () {
			$form.find('[type="submit"]').prop('disabled', true).addClass('elgg-state-disabled');
			spinner.start();
		},
		complete: function () {
			$form.find('[type="submit"]').prop('disabled', false).removeClass('elgg-state-disabled');
			spinner.stop();
		},
		success: function (data) {
			if (data.status >= 0) {
				lightbox.close();
			}

			if (data.system_messages) {
				system_messages.error(data.system_messages.error);
				system_messages.success(data.system_messages.success);
			}
		}
	});
});
