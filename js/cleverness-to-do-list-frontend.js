jQuery( document ).ready( function( $ ) {

	$( function() {
		$cleverness_todo_dateformat = $( "#cleverness_todo_format" ).val();
		$( "#cleverness_todo_deadline" ).datepicker( { dateFormat:$cleverness_todo_dateformat } );
	} );

	$( '.todo-checkbox' ).click( function () {
		var status = 1;
		var id = $( this ).attr( 'id' ).substr( 5 );
		var todoid = '#todo-' + id;
		var single = $ (this ).hasClass( 'single' );
		if ($( this ).prop( 'checked' ) == false ) status = 0;

		var data = {
			action: 'cleverness_todo_complete',
			cleverness_id: id,
			cleverness_status: status,
			_ajax_nonce: ctdl.NONCE
		};

		jQuery.post( ctdl.AJAX_URL, data, function( response ) {
			if ( single != true ) {
				$( todoid ).fadeOut();
				$( '.todo-checkbox' ).removeAttr( "checked" );
			}
		} );
	} );


	/* Delete To-Dos */
	$( '.delete-todo' ).live( 'click', function( e ) {
		e.preventDefault();
		var confirmed = confirm( ctdl.CONFIRMATION_MSG );
		if ( confirmed == false ) return;
		var _item = this;
		var todotr = $( _item ).closest( 'tr' );

		$.ajax( {
    	    type:'post',
        	url: ctdl.AJAX_URL,
	    	data: {
				action: 'cleverness_delete_todo',
				cleverness_todo_id: $( todotr ).attr( 'id' ).substr( 5 ),
				_ajax_nonce: ctdl.NONCE
			},
        	success: function( data ) {
				if ( data == 1 ) {
					$( _item ).parent().html( '<p>'+ctdl.SUCCESS_MSG+'</p>' ) // replace edit and delete buttons with message
					$( todotr ).css( 'background', '#FFFFE0' ).delay( 2000 ).fadeOut( 400, function () { // change the table row background, fade, and remove row, re-stripe
						$( '#todo-list tbody tr' ).removeClass( 'alternate' );
						$( '#todo-list tbody tr:visible:even' ).addClass( 'alternate' );
					});
				} else if ( data == 0 ) {
					$( '#message' ).html( '<p>'+ctdl.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
					$( todotr ).css( 'background', '#FFEBE8' );
				} else if ( data == -1 ) {
					$( '#message' ).html( '<p>'+ctdl.PERMISSION_MSG+'</p>' ).show().addClass( 'error below-h2' );
				}
      		},
      	    error: function( r ) {
				$( '#message' ).html( '<p>'+ctdl.ERROR_MSG+'</p>' ).show().addClass( 'error below-h2' );
				$( todotr ).css( 'background', '#FFEBE8' );
			}
    	} );
	} );
	/* end Delete To-Dos */

} );