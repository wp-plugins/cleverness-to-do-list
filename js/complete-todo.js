jQuery(document).ready(function($) {

// add nonce

	$('.todo-checkbox').click(function () {
		var status = 1;
		var id = $(this).attr('id').substr(5);
		var blah = $(this).attr('id');
		var todoid = '#todo-' + id;
		if ($(this).prop('checked') == false ) status = 0;

		var data = {
		action: 'cleverness_todo_complete',
		cleverness_id: id,
		cleverness_status: status
		};

		jQuery.post(ajaxurl, data, function(response) {
			$(todoid).fadeOut();
			// add the row to the correct table
			});
	});
});