<<<<<<< .mine
<?php
/**
 * Cleverness To-Do List Plugin Main Class
 *
 * The main to-do list class
 * @author C.M. Kendrick <cindy@cleverness.org>
 * @package cleverness-to-do-list
 * @version 3.0
 */

/**
 * Main class
 * @package cleverness-to-do-list
 * @subpackage includes
 */
class ClevernessToDoList {
	protected $cat_id = '';
	public $list = '';
	protected $form = '';

	public function __construct() {
		add_action( 'init', array( &$this, 'cleverness_todo_checklist_init' ) );
		}

	/**
	 * Display a to-do list
	 */
	public function display() {
		list( $priorities, $user, $url, $action ) = CTDL_Lib::set_variables();

		if ( is_admin() ) $this->list .= '<div class="wrap"><div class="icon32"><img src="'.CTDL_PLUGIN_URL.'/images/cleverness-todo-icon.png" alt="" /></div> <h2>'.__('To-Do List', 'cleverness-to-do-list').'</h2>';

		// get the existing to-do data and show the edit form if editing a to-do item
		if ( $action == 'edit-todo' ) {
			$this->edit_todo_item( $url );
			return;
		}

		// otherwise, display the list of to-do items
		if ( is_admin() ) $this->list .= '<h3>'.__( 'To-Do Items', 'cleverness-to-do-list' );
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			$this->list .= ' (<a href="#addtodo">'.__( 'Add New Item', 'cleverness-to-do-list' ).'</a>)';
		 	}
		if ( is_admin() ) $this->list .= '</h3>';

		$this->list .= '<table id="todo-list" class="todo-table widefat">';
		$this->show_table_headings();

		$this->loop_through_todos( $user, $priorities, $url );

		$this->list .= '</table>';

		/* Show completed items in admin */
		if ( is_admin() ) {
			$this->list .= '<h3>'.__( 'Completed Items', 'cleverness-to-do-list' );
			if ( current_user_can( CTDL_Loader::$settings['purge_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
				$cleverness_todo_purge_nonce = wp_create_nonce( 'todopurge' );
				$this->list .= ' (<a id="delete-all-todos" href="admin.php?page=cleverness-to-do-list&amp;action=purgetodo&_wpnonce='.esc_attr( $cleverness_todo_purge_nonce ).'">'.__('Delete All', 'cleverness-to-do-list').'</a>)';
		 	}
			if ( is_admin() ) $this->list .= '</h3>';

			$this->list .= '<table id="todo-list-completed" class="todo-table widefat">';
			$this->show_table_headings( 1 );

			$this->loop_through_todos( $user, $priorities, $url, 1 );

			$this->list .= '</table>';
		}

		$this->list .= $this->create_new_todo_form( $url );

		if ( is_admin() ) $this->list .= '</div>';

		wp_reset_postdata();
	}

	/**
	 * Loop through to-do items
	 * @param $user
	 * @param $priorities
	 * @param $url
	 * @param int $completed
	 * @param int $cat_id
	 */
	protected function loop_through_todos( $user, $priorities, $url, $completed = 0, $cat_id = 0 ) {
		if ( CTDL_Loader::$settings['categories'] == '1' && CTDL_Loader::$settings['sort_order'] == 'cat_id' && $cat_id == 0 ) {

			$categories = CTDL_Categories::get_categories();
			$items = 0;
			$visible = 0;
			$posts_to_exclude = array();
			$visibility = get_option( 'CTDL_categories' );

			foreach ( $categories as $category) {
				if ( !is_admin() ) {
					$visible = $visibility["category_$category->term_id"];
				}

				$todo_items = CTDL_Lib::get_todos( $user, -1, $completed, $category->term_id );

				if ( $todo_items->have_posts() ) {
					array_splice( $posts_to_exclude, count( $posts_to_exclude ), 0, $this->show_todo_list_items( $todo_items, $priorities, $url, $completed, $visible ) );
					$items = 1;
				}
			}

			$todo_items = CTDL_Lib::get_todos( $user, -1, $completed, 0, $posts_to_exclude );
			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $priorities, $url, $completed );
				$items = 1;
			}

			if ( $items == 0 ) {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</td></tr>';
				} else {
					$this->list .= '<tr><td>'.__( 'No completed items.', 'cleverness-to-do-list' ).'</td></tr>';
				}
			}

		} else {

			$todo_items = CTDL_Lib::get_todos( $user, -1, $completed );

			if ( $todo_items->have_posts() ) {
				$this->show_todo_list_items( $todo_items, $priorities, $url, $completed );
			} else {
				if ( $completed == 0 ) {
					$this->list .= '<tr><td>'.__( 'No items to do.', 'cleverness-to-do-list' ).'</td></tr>';
				} else {
					$this->list .= '<tr><td>'.__( 'No completed items.', 'cleverness-to-do-list' ).'</td></tr>';
				}
			}

		}
	}

	/**
	 * Generate the To-Do List
	 * @param object $todo_items
	 * @param array $priorities
	 * @param string $url
	 * @param int $completed
	 * @param int $visible
	 * @return array $posts_to_exclude
	 */
	protected function show_todo_list_items( $todo_items, $priorities, $url, $completed = 0, $visible = 0 ) {

		while ( $todo_items->have_posts() ) : $todo_items->the_post();
			$id = get_the_ID();
			$posts_to_exclude[] = $id;

			if ( $visible == 0 ) {
				$priority = get_post_meta( $id, '_priority', true );
				$priority_class = '';
				if ( $priority == '0' ) $priority_class = ' class="todo-important"';
				if ( $priority == '2' ) $priority_class = ' class="todo-low"';

				$this->list .= '<tr id="todo-'.esc_attr( $id ).'"'.$priority_class.'>';
				$this->show_id( $id );
				$this->show_checkbox( $id, $completed );
				$this->show_todo_text( get_the_content() );
				$this->show_priority( $priority, $priorities );
				$this->show_assigned( get_post_meta( $id, '_assign', true ) );
				$this->show_deadline( get_post_meta( $id, '_deadline', true ) );
				if ( $completed == 1 ) $this->show_completed( get_post_meta( $id, '_completed', true ) );
				$this->show_progress( get_post_meta( $id, '_progress', true ) );
				$this->show_category( get_the_terms( $id, 'todocategories' ) );
				$this->show_addedby( get_the_author() );
				$this->show_edit_link( $id, $url );
				$this->list .= '</tr>';
			}
		endwhile;

		return $posts_to_exclude;

	}

	/**
	 * Get the to-do item data and display the edit form
	 * @param $url
	 */
	protected function edit_todo_item( $url ) {
		$id = absint( $_GET['id'] );
		$todo_item = CTDL_Lib::get_todo( $id );
		$this->list .= $this->create_edit_todo_form( $todo_item, $url );
		if ( is_admin() ) $url = 'admin.php?page=cleverness-to-do-list';
		$this->list .= '<p><a href="'.$url.'">&laquo; '.__( 'Return to To-Do List', 'cleverness-to-do-list' ).'</a></p>';
	}

	/**
	 * Creates the HTML for the form used to edit a to-do item
	 * @param $todo_item
	 * @param string $url The URL the form should be submitted to
	 * @return string Form HTML
	 */
	protected function create_edit_todo_form( $todo_item, $url ) {
			$id = $todo_item->ID;
			if ( is_admin() ) $url = 'admin.php?page=cleverness-to-do-list'; else $url = strtok( $url, "?" );
			$this->form = '';

			if ( is_admin() ) $this->form .= '<h3>'.__( 'Edit To-Do Item', 'cleverness-to-do-list' ).'</h3>';

    	    $this->form .= '<form name="edittodo" id="edittodo" action="'.$url.'" method="post"><table class="todo-form form-table">';
			$this->create_priority_field( get_post_meta( $id, '_priority', true ) );
			$this->create_assign_field( get_post_meta( $id, '_assign', true ) );
			$this->create_deadline_field( get_post_meta( $id, '_deadline', true ) );
			$this->create_progress_field( get_post_meta( $id, '_progress', true ) );
			$this->create_category_field( get_the_terms( $id, 'todocategories' ) );
			$this->create_todo_text_field( $todo_item->post_content );
			$this->form .= '</table>'.wp_nonce_field( 'todoupdate', 'todoupdate', true, false ).'<input type="hidden" name="action" value="updatetodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.__( 'Edit To-Do Item', 'cleverness-to-do-list' ).'" /></p>
				<input type="hidden" name="id" value="'. absint( $id ).'" />';
			$this->form .= '</form>';

		return $this->form;
	}

	/**
	 * Creates the HTML form to add a new to-do item
	 * @param string $url
	 * @return string Form HTML
	 */
	protected function create_new_todo_form( $url ) {
		if ( current_user_can( CTDL_Loader::$settings['add_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {

			if ( is_admin() ) $url = 'admin.php?page=cleverness-to-do-list';

   	 	    $this->form = '<h3>'.__( 'Add New To-Do Item', 'cleverness-to-do-list' ).'</h3>';

    	    $this->form .= '<form name="addtodo" id="addtodo" action="'.$url.'" method="post">
	  		    <table class="todo-form form-table">';
				$this->create_priority_field();
				$this->create_assign_field();
				$this->create_deadline_field();
				$this->create_progress_field();
				$this->create_category_field();
				$this->create_todo_text_field();
				$this->form .= '</table>'.wp_nonce_field( 'todoadd', 'todoadd', true, false ).'<input type="hidden" name="action" value="addtodo" />
        	    <p class="submit"><input type="submit" name="submit" class="button-primary" value="'.__( 'Add To-Do Item', 'cleverness-to-do-list' ).'" /></p>';
			$this->form .= '</form>';

			return $this->form;
		} else {
			return '';
		}
	}

	/**
	 * Creates the HTML for the Priority Form Field
	 * @param int $priority Existing field data
	 */
	protected function create_priority_field( $priority = NULL ) {
		$selected = '';
		$this->form .= '<tr>
		  	<th scope="row"><label for="cleverness_todo_priority">'.__( 'Priority', 'cleverness-to-do-list' ).'</label></th>
		  	<td>
        		<select id="cleverness_todo_priority" name="cleverness_todo_priority">';
					if ( isset( $priority ) ) $selected = ( $priority == 0 ? ' selected = "selected"' : '' );
					$this->form .= sprintf( '<option value="0"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_0'] );
					if ( isset( $priority ) ) {
						$selected = ( $priority == 1 ? ' selected' : '' );
						} else {
							$selected = ' selected="selected"';
						}
					$this->form .= sprintf( '<option value="1"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_1'] );
					$selected = '';
					if ( isset( $priority ) ) $selected = ( $priority == 2 ? ' selected' : '' );
					$this->form .= sprintf( '<option value="2"%s>%s</option>', $selected, CTDL_Loader::$settings['priority_2'] );
        		$this->form .= '</select>
		  	</td>
			</tr>';
	}

	/**
	 * Creates the HTML for the Assign to Use Field
	 * @param int $assign Existing field data
	 */
	protected function create_assign_field( $assign = NULL ) {
		if ( CTDL_Loader::$settings['assign'] == '0' && current_user_can( CTDL_Loader::$settings['assign_capability'] ) ) {
			$selected = '';
			$this->form .= '<tr>
		  		<th scope="row"><label for="cleverness_todo_assign">'.__( 'Assign To', 'cleverness-to-do-list' ).'</label></th>
		  		<td>
					<select name="cleverness_todo_assign" id="cleverness_todo_assign">';
					if ( isset( $assign ) && $assign == '-1' ) $selected = ' selected="selected"';
					$this->form .= sprintf( '<option value="-1"%s>%s</option>', $selected, __( 'None', 'cleverness-to-do-list' ) );

					if ( CTDL_Loader::$settings['user_roles'] == '' ) {
						$roles = array( 'contributor', 'author', 'editor', 'administrator' );
					} else {
						$roles = explode( ", ", CTDL_Loader::$settings['user_roles'] );
						}
					foreach ( $roles as $role ) {
						$role_users = CTDL_Lib::get_users( $role );
						foreach( $role_users as $role_user ) {
							$selected = '';
							$user_info = get_userdata( $role_user->ID );
							if ( isset( $assign ) && $assign == $role_user->ID ) $selected = ' selected="selected"';
							$this->form .= sprintf( '<option value="%d"%s>%s</option>', $role_user->ID, $selected, $user_info->display_name );
						}
					}

					$this->form .= '</select>
				</td>
			</tr>';
		}
	}

	/**
	 * Creates the HTML for the Deadline Field
	 * @param string $deadline Existing field data
	 */
	protected function create_deadline_field( $deadline = NULL ) {
		if ( CTDL_Loader::$settings['show_deadline'] == '1' ) {
			$value = ( isset( $deadline ) && $deadline != 0 ? $deadline : '' );
			$this->form .= sprintf( '<tr>
				<th scope="row"><label for="cleverness_todo_deadline">%s</label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="%s" /></td>
			</tr>', __( 'Deadline', 'cleverness-to-do-list' ), esc_attr( $value ) );
		}
	}

	/**
	 * Creates the HTML for the Progress Field
	 * @param int $progress Existing field data
	 */
	protected function create_progress_field( $progress = NULL ) {
		if ( CTDL_Loader::$settings['show_progress'] == '1' ) {
			$this->form .= '<tr>
				<th scope="row"><label for="cleverness_todo_progress">'.__( 'Progress', 'cleverness-to-do-list' ).'</label></th>
				<td><select id="cleverness_todo_progress" name="cleverness_todo_progress">';
				$i = 0;
				while ( $i <= 100 ) {
					$this->form .= '<option value="'.$i.'"';
					if ( isset( $progress ) && $progress == $i ) $this->form .= ' selected="selected"';
					$this->form .= '>'.$i.'</option>';
					$i += 5;
				}
				$this->form .= '</select></td>
			</tr>';
		}
	}

	/**
	 * Creates the HTML for the Category Field
	 * @param int $cat_id Existing field data
	 */
	protected function create_category_field( $cat_id = NULL ) {
		if ( CTDL_Loader::$settings['categories'] == '1' ) {
			$cat_id = ( $cat_id != NULL ? $cat_id[0]->term_id : 0 );
			$this->form .= '<tr><th scope="row"><label for="cat">'.__( 'Category', 'cleverness-to-do-list' ).'</label></th><td>'.
				wp_dropdown_categories( 'taxonomy=todocategories&echo=0&orderby=name&hide_empty=0&show_option_none='.__( 'None', 'cleverness-to-do-list' ).'&selected='.$cat_id ).'</td></tr>';
		}
	}

	/**
	 * Creates the HTML for the To-Do Text Field
	 * @param array $todo_text Existing field data
	 */
	protected function create_todo_text_field( $todo_text = NULL ) {
		$text = ( isset( $todo_text ) ? stripslashes( esc_html( $todo_text, 1 ) ) : '' );
		$this->form .= sprintf( '<tr>
        	<th scope="row"><label for="cleverness_todo_description">%s</label></th>
        	<td><textarea id="cleverness_todo_description" name="cleverness_todo_description" rows="5" cols="50">%s</textarea></td>
			</tr>', __( 'To-Do', 'cleverness-to-do-list' ), $text );
	}

	/**
	 * Creates the HTML for the To-Do List Table Headings
	 * @param $completed
	 */
	protected function show_table_headings( $completed = 0 ) {
		$this->list .= '<thead><tr>';
		if ( !is_admin() ) $this->list .= '<th></th>';
		if ( CTDL_Loader::$settings['show_id'] ) $this->list .= '<th>'.__( 'ID', 'cleverness-to-do-list' ).'</th>';
		$this->list .= '<th>'.__( 'Item', 'cleverness-to-do-list' ).'</th>';
	  	$this->list .= '<th>'.__( 'Priority', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['assign'] == 0  && (CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
			&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
			&& CTDL_Loader::$settings['assign'] == 0 ) $this->list .= '<th>'.__( 'Assigned To', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) $this->list .= '<th>'.__( 'Deadline', 'cleverness-to-do-list' ).'</th>';
		if ( $completed == 1 && CTDL_Loader::$settings['show_completed_date'] == 1) $this->list .= '<th>'.__('Completed', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) $this->list .= '<th>'.__( 'Progress', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['categories'] == 1 ) $this->list .= '<th>'.__( 'Category', 'cleverness-to-do-list' ).'</th>';
		if ( CTDL_Loader::$settings['list_view'] == 1  && CTDL_Loader::$settings['todo_author'] == 0 ) $this->list .= '<th>'.__ ('Added By', 'cleverness-to-do-list' ).'</th>';
		if ( current_user_can(CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) $this->list .= '<th>'.__( 'Action', 'cleverness-to-do-list' ).'</th>';
    	$this->list .= '</tr></thead>';
	}

	/**
	 * Show the ID for a To-Do Item
	 * @param int $id
	 */
	protected function show_id( $id ) {
		if ( CTDL_Loader::$settings['show_id'] == 1 ) {
			$this->list .= ( $id != '' ? sprintf( '<td>%s</td>', esc_attr( $id ) ) : '<td></td>' );
		}
	}

	/**
	 * Create the HTML to show a To-Do List Checkbox
	 * @param int $id
	 * @param boolean $completed
	 * @param string $layout
	 * @param string $single
	 */
	protected function show_checkbox( $id, $completed = NULL, $layout = 'table', $single = '' ) {
		$permission = CTDL_Lib::check_permission( 'todo', 'complete' );
		if ( $permission === true ) {
			if ( $layout == 'table' ) $this->list .= '<td>';
			if ( $completed == 1 ) {
				$this->list .= sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox completed'.$single.'" checked="checked" />', esc_attr( $id ) );
			} else {
				$this->list .= sprintf( '<input type="checkbox" id="ctdl-%d" class="todo-checkbox uncompleted'.$single.'"/>', esc_attr( $id ) );
			}
			$cleverness_todo_complete_nonce = wp_create_nonce( 'todocomplete' );
			$this->list .= '<input type="hidden" name="cleverness_todo_complete_nonce" value="'.esc_attr( $cleverness_todo_complete_nonce ).'" />';
			if ( !is_admin() && $layout == 'table' ) $this->list .= '</td>';
		}
	}

	/**
	 * Show the To-Do Text
	 * @param string $todo_text
	 * @param string $layout
	 */
	public function show_todo_text( $todo_text, $layout = 'table' ) {
		if ( !is_admin() && $layout == 'table' ) {
			$this->list .= '<td>';
		} elseif ( is_admin() ) {
			$this->list .= '&nbsp;';
		}
		$this->list .= stripslashes( $todo_text );
		if ( $layout == 'table' ) $this->list .= '</td>';
	}

	/**
	 * Show the Edit To-Do Link
	 * @param int $id
	 * @param string $url
	 */
	protected function show_edit_link( $id, $url ) {
		$edit = '';
		$url = $url.'?action=edit-todo&amp;id='.$id;
		if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			if ( is_admin() ) {
				$edit = '<input class="edit-todo button-secondary" type="button" value="'. __( 'Edit' ).'" />';
			} else {
				$edit = '<a href="'.$url.'" class="edit-todo">'.__( 'Edit' ).'</a>';
				}
			}
		if ( current_user_can( CTDL_Loader::$settings['delete_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' ) {
			if ( is_admin() ) {
				$edit .= ' <input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
			} else {
				$edit .= ' | <a href="" class="delete-todo">'.__( 'Delete' ).'</a>';
				}
			}
	  	if ( current_user_can( CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == '0' )
			$this->list .= '<td>'.$edit.'</td>';
	}

	/**
	 * Show the Priority Level of a To-Do Item
	 * @param int $the_priority
	 * @param array $priority
	 */
	public function show_priority( $the_priority, $priority ) {
		$this->list .= sprintf( '<td>%s</td>', esc_attr( $priority[$the_priority] ) );
	}

	/**
	 * Show the User that a To-Do Item is Assigned To
	 * @param int $assign
	 * @param string $layout
	 */
	public function show_assigned( $assign, $layout = 'table' ) {
		if ( ( ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0 && ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) ||
		( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1) ) && CTDL_Loader::$settings['assign'] == 0 ) {
			if ( $assign != '-1' && $assign != '' && $assign != 0 ) {
				$assign_user = get_userdata( $assign );
				if ( $layout == 'table' ) {
					$this->list .= '<td>'.esc_attr( $assign_user->display_name ).'</td>';
				} else {
					$this->list .= esc_attr( $assign_user->display_name );
				}
			} else {
				if ( $layout == 'table' ) $this->list .= '<td></td>';
			}
		}
   	}

	/**
	 * Show the Category that a To-Do Item is In
	 * @param array $categories
	 */
	public function show_category( $categories ) {
		if ( CTDL_Loader::$settings['categories'] == '1' ) {
			$this->list .= '<td>';
			if ( $categories != NULL ) {
				foreach( $categories as $category ) {
					$this->list .= esc_attr( $category->name );
				}
			}
			$this->list .= '</td>';
		}
	}

	/**
	 * Show Who Added a To-Do Item
	 * @param int $author
	 * @param string $layout
	 */
	public function show_addedby( $author, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['todo_author'] == 0 ) {
			if ( $layout == 'table' ) {
				$this->list .= '<td>'.esc_attr( $author ).'</td>';
			} else {
				$this->list .= esc_attr( $author );
			}
		}
	}

	/**
	 * Show the Deadline for a To-Do Item
	 * @param string $deadline
	 * @param string $layout
	 */
	public function show_deadline( $deadline, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $deadline != '' ? sprintf( '<td>%s</td>', esc_attr( $deadline ) ) : '<td></td>' );
			} else {
				$this->list .= ( $deadline != '' ? sprintf( '%s', esc_attr( $deadline ) ) : '' );
			}
		}
	}

	/**
	 * Show the Date that a To-Do Item was Completed
	 * @param string $completed
	 * @param string $layout
	 */
	public function show_completed( $completed, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_completed_date'] && $completed != '0000-00-00 00:00:00' ) {
			$date = ( isset( $completed ) ? date( CTDL_Loader::$settings['date_format'], strtotime( $completed ) ) : '' );
			if ( $layout == 'table' ) {
				$this->list .= '<td>'.esc_attr( $date ).'</td>';
			} else {
				$this->list .= esc_attr( $date );
			}
		}
	}

	/**
	 * Show the Progress of a To-Do Item
	 * @param int $progress
	 * @param string $layout
	 */
	public function show_progress( $progress, $layout = 'table' ) {
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) {
			if ( $layout == 'table' ) {
				$this->list .= ( $progress != '' ? sprintf( '<td>%d%%</td>', esc_attr( $progress ) ) : '<td></td>' );
			} else {
				$this->list .= ( $progress != '' ? sprintf( '%d%%', esc_attr( $progress ) ) : '' );
			}

		}
	}

} // end class
?>=======
<?php
/* Main class */

class ClevernessToDoList {
	protected $settings;

	public function __construct($settings) {
		add_action( 'init', array(&$this, 'cleverness_todo_checklist_init') );
		$this->settings = $settings;
		}

public function display() {
	global $wpdb, $cleverness_todo_option, $userdata, $current_user;
	get_currentuserinfo();

	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);


	$cleverness_todo_settings = get_option('cleverness_todo_settings');

	$user = $this->get_user($current_user, $userdata);

	// get to-do items
	$results = cleverness_todo_get_todos($user, 0);

	if ($results) {
		$catid = '';
		foreach ($results as $result) {
			$user_info = get_userdata($result->author);
			$priority_class = '';
		   	if ($result->priority == '0') $priority_class = ' class="todo-important"';
			if ($result->priority == '2') $priority_class = ' class="todo-low"';

			if ( $cleverness_todo_settings['categories'] == '1' && $result->cat_id != 0 ) {
				$cat = cleverness_todo_get_cat_name($result->cat_id);
				if ( $catid != $result->cat_id  && $cat->name != '' ) echo '<h4>'.$cat->name.'</h4>';
				$catid = $result->cat_id;
			}
			echo '<p id="todo-'.$result->id.'">';

			$this->show_checkbox($result);
			$this->show_todo_text($result, $priority_class);
			$this->show_assigned($result);
			$this->show_deadline($result);
			$this->show_progress($result);
			$this->show_addedby($result, $user_info);
			$this->show_edit_link($result);

			echo '</p>';
			}
	} else {
		echo '<p>'.__('No items to do.', 'cleverness-to-do-list').'</p>';
		}
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add' );
	if ( $cleverness_todo_permission === true ) {
		echo '<p style="text-align: right">'. '<a href="admin.php?page=cleverness-to-do-list#addtodo">'. __('New To-Do Item &raquo;', 'cleverness-to-do-list').'</a></p>';
	}

}

	protected function get_user($current_user, $userdata) {
		if ( $this->settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
		   	$user = $userdata->ID;
		   	}
		return $user;
		}

	protected function show_checkbox($result, $priority_class = '') {
		$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );
		if ( $cleverness_todo_permission === true ) {
			echo '<input type="checkbox" id="ctdl-'.$result->id.'" class="todo-checkbox"/>';
			}
		}

	protected function show_todo_text($result) {
		echo '<td>'.stripslashes($result->todotext).'</td>';
		}

	protected function show_edit_link($result) {
		if ( current_user_can($this->settings['edit_capability']) || $this->settings['list_view'] == '0' ) {
			echo '<input class="edit-todo button-secondary" type="button" value="'. _e( 'Edit' ).'" />';
			}
		}

	protected function show_assigned($result) {
		if ( ($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '0' && (current_user_can($this->settings['view_all_assigned_capability']))) ||
		($this->settings['list_view'] == '1' && $this->settings['show_only_assigned'] == '1') && $this->settings['assign'] == '0') {
			$assign_user = '';
			if ( $result->assign != '-1' && $result->assign != '' && $result->assign != '0') {
				$assign_user = get_userdata($result->assign);
				echo '<td>'.$assign_user->display_name.'</td>';
			} else {
				echo '<td></td>';
				}
			}
   		}

	protected function show_addedby($result, $user_info) {
		if ( $this->settings['list_view'] == '1' && $this->settings['todo_author'] == '0' ) {
			if ( $result->author != '0' ) {
				echo '<td>'.$user_info->display_name.'</td>';
			} else {
				echo '<td></td>';
				}
			}
		}

	protected function show_deadline($result) {
		if ( $this->settings['show_deadline'] == '1' && $result->deadline != '' ) {
			echo '<td>'.$result->deadline.'</td>';
			}
		}

	protected function show_progress($result) {
		if ( $this->settings['show_progress'] == '1' && $result->progress != '' ) {
			echo '<td>'.$result->progress.'</td>';
			}
		}

/* JS and Ajax Setup */
// returns various JavaScript vars needed for the scripts
public function cleverness_todo_checklist_get_js_vars() {
	return array(
	'SUCCESS_MSG' => __('To-Do Deleted.', 'cleverness-to-do-list'),
	'ERROR_MSG' => __('There was a problem performing that action.', 'cleverness-to-do-list'),
	'PERMISSION_MSG' => __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list'),
	'EDIT_TODO' => __('Edit To-Do', 'cleverness-to-do-list'),
	'PUBLIC' => __('Public', 'cleverness-to-do-list'),
	'PRIVATE' => __('Private', 'cleverness-to-do-list'),
	'CONFIRMATION_MSG' => __("You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list'),
	'NONCE' => wp_create_nonce('cleverness-todo'),
	'AJAX_URL' => admin_url('admin-ajax.php')
	);
}

public function cleverness_todo_checklist_init() {
	wp_register_script( 'cleverness_todo_checklist_complete_js', CTDL_PLUGIN_URL.'/js/complete-todo.js', '', 1.0, true );
	add_action('wp_enqueue_scripts', array(&$this, 'cleverness_todo_checklist_add_js') );
	add_action('wp_ajax_cleverness_todo_complete', array(&$this, 'cleverness_todo_checklist_complete_callback') );
}

public function cleverness_todo_checklist_add_js() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'cleverness_todo_checklist_complete_js' );
	wp_localize_script( 'cleverness_todo_checklist_complete_js', 'cltd', $this->cleverness_todo_checklist_get_js_vars() );
    }

public function cleverness_todo_checklist_complete_callback() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_widget_id = intval($_POST['cleverness_id']);
		$message = cleverness_todo_complete($cleverness_widget_id, 1);
	} else {
		$message = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	}

	die(); // this is required to return a proper result
}
}


?>>>>>>>> .r510187