jQuery(document).ready(function($) {

	$('.to-do-checkbox').click(function () {
		var id = $(this).attr('id').substr(5);
		var todoid = '#todo-' + id;

		var data = {
		action: 'cleverness_todo_complete',
		cleverness_widget_id: id
		};

		jQuery.post(ajaxurl, data, function(response) {
			$(todoid).fadeOut();
			});
	});
});