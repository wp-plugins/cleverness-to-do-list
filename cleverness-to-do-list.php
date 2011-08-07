<?php
/*
Plugin Name: Cleverness To-Do List
Version: 2.2.8
Description: Manage to-do list items on a individual or group basis with categories. Includes a dashboard widget and a sidebar widget.
Author: C.M. Kendrick
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list/
*/

/*
Based on the ToDo plugin by Abstract Dimensions with a patch by WordPress by Example.
*/

global $wp_version;

$exit_msg = __('To-Do List requires WordPress 3.2 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list');

if (version_compare($wp_version, "3.2", "<")) {
	exit($exit_msg);
  	}

define( 'CTDL_BASENAME', plugin_basename(__FILE__) );
define( 'CTDL_PLUGIN_DIR', plugin_dir_path( __FILE__) );
define( 'CTDL_PLUGIN_URL', plugins_url('', __FILE__) );
if ( ! function_exists( 'is_plugin_active_for_network' ) )
   require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
if ( is_plugin_active_for_network( CTDL_BASENAME ) ) {
	$prefix = $wpdb->base_prefix;
} else {
	$prefix = $wpdb->prefix;
}
define( 'CTDL_TODO_TABLE', $prefix.'todolist' );
define( 'CTDL_CATS_TABLE', $prefix.'todolist_cats' );
define( 'CTDL_STATUS_TABLE', $prefix.'todolist_status' );

include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-options.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-dashboard-widget.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-widget.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-shortcode.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-categories.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-help.php');
include_once(CTDL_PLUGIN_DIR . 'includes/cleverness-to-do-list-functions.php');


/* 	WANT TO BE ABLE TO REMOVE OPTION FROM GLOBAL */
$cleverness_todo_option = get_option('cleverness_todo_settings');

$action = '';
if ( isset($_GET['action']) ) $action = $_GET['action'];
if ( isset($_POST['action']) ) $action = $_POST['action'];

switch($action) {

case 'setuptodo':
	cleverness_todo_install();
	break;

case 'addtodo':
	$message = '';
	if ( $_POST['cleverness_todo_description'] != '' ) {
		$todotext = $_POST['cleverness_todo_description'];
		$priority = $_POST['cleverness_todo_priority'];
		$assign = (  isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
		$deadline = (  isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
		$progress = (  isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
		$category = (  isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );

		require_once (ABSPATH . WPINC . '/pluggable.php');
		if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'todoadd') ) die('Security check failed');
		if ( $cleverness_todo_option['email_assigned'] == '1' && $cleverness_todo_option['assign'] == '0' )
			$message = cleverness_todo_email_user($todotext, $priority, $assign, $deadline);
		$message .= cleverness_todo_insert($todotext, $priority, $assign, $deadline, $progress, $category);
	} else {
		$message = __('To-Do cannot be blank.', 'cleverness-to-do-list');
	}
	break;

case 'updatetodo':
	$id = $_POST['id'];
	$todotext = $_POST['cleverness_todo_description'];
	$priority = $_POST['cleverness_todo_priority'];
	$assign = (  isset($_POST['cleverness_todo_assign']) ?  $_POST['cleverness_todo_assign'] : 0 );
	$deadline = (  isset($_POST['cleverness_todo_deadline']) ?  $_POST['cleverness_todo_deadline'] : '' );
	$progress = (  isset($_POST['cleverness_todo_progress']) ?  $_POST['cleverness_todo_progress'] : 0 );
	$category = (  isset($_POST['cleverness_todo_category']) ?  $_POST['cleverness_todo_category'] : '' );
	require_once (ABSPATH . WPINC . '/pluggable.php');
	if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'todoupdate') ) die('Security check failed');
	$message = cleverness_todo_update($id, $priority, $todotext, $assign, $deadline, $progress, $category);
	break;

case 'completetodo':
	$id = intval($_GET['id']);
	$message = cleverness_todo_complete($id, '1');
	break;

case 'uncompletetodo':
	$id = intval($_GET['id']);
	$message = cleverness_todo_complete($id, '0');
	break;

case 'purgetodo':
	$message = cleverness_todo_purge();
	break;

} // end switch


/* Create admin page */
function cleverness_todo_subpanel() {
   	global $wpdb, $userdata, $cleverness_todo_option, $message, $current_user;
   	get_currentuserinfo();

   	$table_name = $wpdb->prefix.'todolist'; // REMOVE
	$status_table_name = $wpdb->prefix.'todolist_status'; // REMOVE
   	$priority = array(0 => $cleverness_todo_option['priority_0'] , 1 => $cleverness_todo_option['priority_1'], 2 => $cleverness_todo_option['priority_2']);
	?>

	<div id="message"><?php if ( $cleverness_todo_message != '' ) echo '<p class="error below-h2">'.$cleverness_todo_message.'</p>'; ?></div>

	<?php
	/* Display this section if editing an existing to-do item */
	$action = ( isset($_GET['action']) ? $_GET['action'] : '' );
	if ($action == 'edittodo') {
    	$id = absint($_GET['id']);
    	$todo = cleverness_todo_get_todo($id);
	?>

	<div class="wrap">
 		<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div> <h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
 		<h3><?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?></h3>
 		<form name="edittodo" action="" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="cleverness_todo_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
					<select name="cleverness_todo_priority">
					<option value="0" <?php if ($todo->priority == 0) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_0']; ?>&nbsp;</option>
					<option value="1" <?php if ($todo->priority == 1) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_1']; ?></option>
					<option value="2" <?php if ($todo->priority == 2) { echo "selected"; } ?>><?php echo $cleverness_todo_option['priority_2']; ?></option>
					</select>
					<input type="hidden" name="id" value="<?php echo absint($todo->id); ?>" />
				</td>
			</tr>
			<?php if ($cleverness_todo_option['assign'] == '0' && current_user_can($cleverness_todo_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="cleverness_todo_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td>
				<select name='cleverness_todo_assign' id='cleverness_todo_assign' class=''>
					<option value='-1'<?php if ( $todo->assign == '-1' ) echo ' selected="selected"'; ?>><?php _e('None', 'cleverness-to-do-list') ?></option>
					<?php
					if ( $cleverness_todo_option['user_roles'] == '' ) $roles = array('contributor', 'author', 'editor', 'administrator');
					else $roles = explode(", ", $cleverness_todo_option['user_roles']);
					foreach ( $roles as $role ) {
						$role_users = cleverness_todo_get_users($role);
						foreach($role_users as $role_user){
							$user_info = get_userdata($role_user);
							echo '<option value="'.$role_user.'"';
							if ( $todo->assign == $role_user ) echo ' selected="selected"';
							echo '>'.$user_info->display_name.'</option>';
						}
					}
					?>
				</select>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['assign'] == '0' && !current_user_can($cleverness_todo_option['assign_capability'])) : ?>
				<input type="hidden" name='cleverness_todo_assign' id='cleverness_todo_assign' value="<?php echo $todo->assign; ?>" />
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_deadline'] == '1') : ?>
				<th scope="row"><label for="cleverness_todo_deadline"><?php _e('Deadline', 'cleverness-to-do-list') ?></label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="<?php echo esc_html($todo->deadline, 1); ?>" /></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_progress'] == '1') : // MAKE INTO LOOP!!!!!!!!!!!!!!!!! ?>
				<th scope="row"><label for="cleverness_todo_progress"><?php _e('Progress', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_progress">
					<option value="0" <?php if ($todo->progress == 0) { echo "selected"; } ?>>0</option>
					<option value="5" <?php if ($todo->progress == 5) { echo "selected"; } ?>>5</option>
					<option value="10" <?php if ($todo->progress == 10) { echo "selected"; } ?>>10</option>
					<option value="15" <?php if ($todo->progress == 15) { echo "selected"; } ?>>15</option>
					<option value="20" <?php if ($todo->progress == 20) { echo "selected"; } ?>>20</option>
					<option value="25" <?php if ($todo->progress == 25) { echo "selected"; } ?>>25</option>
					<option value="30" <?php if ($todo->progress == 30) { echo "selected"; } ?>>30</option>
					<option value="35" <?php if ($todo->progress == 35) { echo "selected"; } ?>>35</option>
					<option value="40" <?php if ($todo->progress == 40) { echo "selected"; } ?>>40</option>
					<option value="45" <?php if ($todo->progress == 45) { echo "selected"; } ?>>45</option>
					<option value="50" <?php if ($todo->progress == 50) { echo "selected"; } ?>>50</option>
					<option value="55" <?php if ($todo->progress == 55) { echo "selected"; } ?>>55</option>
					<option value="60" <?php if ($todo->progress == 60) { echo "selected"; } ?>>60</option>
					<option value="65" <?php if ($todo->progress == 65) { echo "selected"; } ?>>65</option>
					<option value="70" <?php if ($todo->progress == 70) { echo "selected"; } ?>>70</option>
					<option value="75" <?php if ($todo->progress == 75) { echo "selected"; } ?>>75</option>
					<option value="80" <?php if ($todo->progress == 80) { echo "selected"; } ?>>80</option>
					<option value="85" <?php if ($todo->progress == 85) { echo "selected"; } ?>>85</option>
					<option value="90" <?php if ($todo->progress == 90) { echo "selected"; } ?>>90</option>
					<option value="95" <?php if ($todo->progress == 95) { echo "selected"; } ?>>95</option>
					<option value="100" <?php if ($todo->progress == 100) { echo "selected"; } ?>>100&nbsp;</option>
					</select></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['categories'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_category"><?php _e('Category', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_category">
					<?php $cats = cleverness_todo_get_cats();
					foreach ( $cats as $cat ) { ?>
					<option value="<?php echo $cat->id; ?>"<?php if ( $todo->cat_id == $cat->id ) echo ' selected="selected"'; ?>><?php echo $cat->name; ?></option>
					<?php } ?>
					</select></td>
			</tr>
			<?php endif; ?>
	   		<tr>
				<th scope="row" valign="top"><label for="cleverness_todo_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
				<td><textarea name="cleverness_todo_description" rows="5" cols="50"><?php echo stripslashes(esc_html($todo->todotext, 1)); ?></textarea></td>
			</tr>
			</table>
			<?php wp_nonce_field( 'todoupdate' ) ?>
			<input type="hidden" name="action" value="updatetodo" />
	  		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?> &raquo;" /></p>
 		</form>
 		<p><a href="admin.php?page=cleverness-to-do-list"><?php _e('&laquo; Return to To-Do List', 'cleverness-to-do-list'); ?></a></p>
	</div>

	<?php
	} else {
	/* Display the to-do list items */
	?>

	<div class="wrap">
   		<div class="icon32"><img src="<?php echo CTDL_PLUGIN_URL; ?>/images/cleverness-todo-icon.png" alt="" /></div>	<h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
		<h3><?php _e('To-Do Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($cleverness_todo_option['add_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
			(<a href="#addtd"><?php _e('Add New Item', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
		<table id="todo-list" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	  		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $cleverness_todo_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_deadline'] == '1' ) : ?><th><?php _e('Deadline', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_progress'] == '1' ) : ?><th><?php _e('Progress', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['categories'] == '1' ) : ?><th><?php _e('Category', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	  		<?php if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($cleverness_todo_option['edit_capability'])|| $cleverness_todo_option['list_view'] == '0') : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>
		<?php
   		if ( $cleverness_todo_settings['list_view'] == '2' ) {
			$user = $current_user->ID;
		} else {
			$user = $userdata->ID;
		}

		// get to-do items
		$results = cleverness_todo_get_todos($user);

   		if ($results) {
	   		foreach ($results as $result) {
		   		$prstr = $priority[ $result->priority ];
		   		$priority_class = '';
		   		$user_info = get_userdata($result->author);
		   		if ($result->priority == '0') $priority_class = ' todo-important';
				if ($result->priority == '2') $priority_class = ' todo-low';
				$edit = '';
				if (current_user_can($cleverness_todo_option['edit_capability']) || $cleverness_todo_option['list_view'] == '0')
		  			$edit = '<input class="edit-todo button-secondary" type="button" value="'. __( 'Edit' ).'" />';
				if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
					$edit .= ' <input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
		   		echo '<tr id="todo-'.$result->id.'" class="'.$priority_class.'">';
				echo '<td><input type="checkbox" id="cltd-'.$result->id.'" class="todo-checkbox" />&nbsp;'.stripslashes($result->todotext).'</td>
			   	<td>'.$prstr.'</td>';
				if ( $cleverness_todo_option['assign'] == '0' ) {
					$assign_user = '';
					if ( $result->assign != '-1' )
						$assign_user = get_userdata($result->assign);
					echo '<td>'.$assign_user->display_name.'</td>';
					}
				if ( $cleverness_todo_option['show_deadline'] == '1' )
					echo '<td>'.$result->deadline.'</td>';
				if ( $cleverness_todo_option['show_progress'] == '1' ) {
					echo '<td>'.$result->progress;
					if ( $result->progress != '' ) echo '%';
					echo '</td>';
					}
				if ( $cleverness_todo_option['categories'] == '1' ) {
					$cat = cleverness_todo_get_cat_name($result->cat_id);
					echo '<td>'.$cat->name.'</td>';
					}
		   		if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' )
		   			echo '<td>'.$user_info->display_name.'</td>';
		   		if (current_user_can($cleverness_todo_option['edit_capability'])|| $cleverness_todo_option['list_view'] == '0')
					echo '<td>'.$edit.'</td></tr>';
	   		}
   		} else {
	   		echo '<tr><td ';
	   		$colspan = 2;
	   		if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['show_progress'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['categories'] == '1' ) $colspan += 1;
			if ( current_user_can($cleverness_todo_option['edit_capability']) || $cleverness_todo_option['list_view'] == '0' ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	   		echo '>'.__('There is nothing to do...', 'cleverness-to-do-list').'</td></tr>';
   			}
		?>
		</table>
	</div>

	<div class="wrap">
		<h3><?php _e('Completed Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($cleverness_todo_option['purge_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
			(<a href="admin.php?page=cleverness-to-do-list&amp;action=purgetodo"><?php _e('Delete All', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
		<table id="todo-list-completed" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	   		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $cleverness_todo_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_deadline'] == '1' ) : ?><th><?php _e('Deadline', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['show_completed_date'] == '1' ) : ?><th><?php _e('Completed', 'cleverness-to-do-list'); ?></th><?php endif; ?>
			<?php if ( $cleverness_todo_option['categories'] == '1' ) : ?><th><?php _e('Category', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	   		<?php if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0') : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>
		<?php
		// individual view
		$results = cleverness_todo_get_todos($user, 0, 1);

   		if ($results) {
	   		foreach ($results as $result) {
		   		$class = ('alternate' == $class) ? '' : 'alternate';
		   		$prstr = $priority[ $result->priority ];
		   		$user_info = get_userdata($result->author);
				$edit = '';
				if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
		   			$edit = '<input class="delete-todo button-secondary" type="button" value="'. __( 'Delete' ).'" />';
		   		echo '<tr id="todo-'.$result->id.'" class="'.$class.'">
			   	<td><input type="checkbox" id="cltd-'.$result->id.'" class="todo-checkbox" checked="checked" />&nbsp;'.stripslashes($result->todotext).'</td>
			   	<td>'.$prstr.'</td>';
				if ( $cleverness_todo_option['assign'] == '0' ) {
					$assign_user = '';
					if ( $result->assign != '-1' )
						$assign_user = get_userdata($result->assign);
					echo '<td>'.$assign_user->display_name.'</td>';
					}
				if ( $cleverness_todo_option['show_deadline'] == '1' )
					echo '<td>'.$result->deadline.'</td>';
				if ( $cleverness_todo_option['show_completed_date'] == '1' ) {
					$date = '';
					if ( $result->completed != '0000-00-00 00:00:00' )
						$date = date($cleverness_todo_option['date_format'], strtotime($result->completed));
					echo '<td>'.$date.'</td>';
					}
				if ( $cleverness_todo_option['categories'] == '1' ) {
					$cat = cleverness_todo_get_cat_name($result->cat_id);
					echo '<td>'.$cat->name.'</td>';
					}
		   		if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' )
		   			echo '<td>'.$user_info->display_name.'</td>';
		  		if (current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0')
					 echo '<td>'.$edit.'</td>
			 	</tr>';
	  	 		}
   		} else {
	  		echo '<tr><td ';
			$colspan = 2;
	   		if ( $cleverness_todo_option['assign'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['list_view'] == '1' && $cleverness_todo_option['todo_author'] == '0' ) $colspan += 1;
			if ( $cleverness_todo_option['show_deadline'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['show_completed_date'] == '1' ) $colspan += 1;
			if ( $cleverness_todo_option['categories'] == '1' ) $colspan += 1;
			if ( current_user_can($cleverness_todo_option['delete_capability']) || $cleverness_todo_option['list_view'] == '0' ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	  	 	echo '>'.__('There are no completed items', 'cleverness-to-do-list').'</td></tr>';
   		}
		?>
   		</table>
	</div>

	<?php // add new to-do
	if (current_user_can($cleverness_todo_option['add_capability']) || $cleverness_todo_option['list_view'] == '0') : ?>
	<div class="wrap">
   	 	<h3><?php _e('Add New To-Do Item', 'cleverness-to-do-list') ?></h3>
    	<form name="addtodo" id="addtodo" action="" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="cleverness_todo_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
        			<select name="cleverness_todo_priority">
       	 				<option value="0"><?php echo $cleverness_todo_option['priority_0']; ?>&nbsp;</option>
        				<option value="1" selected="selected"><?php echo $cleverness_todo_option['priority_1']; ?></option>
       	 		   		<option value="2"><?php echo $cleverness_todo_option['priority_2']; ?></option>
        			</select>
		  		</td>
			</tr>
			<?php if ($cleverness_todo_option['assign'] == '0' && current_user_can($cleverness_todo_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="cleverness_todo_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td>
					<select name='cleverness_todo_assign' id='cleverness_todo_assign' class=''>
					<option value='-1'><?php _e('None', 'cleverness-to-do-list') ?></option>
					<?php
					if ( $cleverness_todo_option['user_roles'] == '' ) $roles = array('contributor', 'author', 'editor', 'administrator');
					else $roles = explode(", ", $cleverness_todo_option['user_roles']);
					foreach ( $roles as $role ) {
						$role_users = cleverness_todo_get_users($role);
						foreach($role_users as $role_user){
							$user_info = get_userdata($role_user);
							echo '<option value="'.$role_user.'">'.$user_info->display_name.'</option>';
						}
					}
					?>
					</select>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_deadline'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_deadline"><?php _e('Deadline', 'cleverness-to-do-list') ?></label></th>
				<td><input type="text" name="cleverness_todo_deadline" id="cleverness_todo_deadline" value="" /></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['show_progress'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_progress"><?php _e('Progress', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_progress">
					<option value="0">0</option>
					<option value="5">5</option>
					<option value="10">10</option>
					<option value="15">15</option>
					<option value="20">20</option>
					<option value="25">25</option>
					<option value="30">30</option>
					<option value="35">35</option>
					<option value="40">40</option>
					<option value="45">45</option>
					<option value="50">50</option>
					<option value="55">55</option>
					<option value="60">60</option>
					<option value="65">65</option>
					<option value="70">70</option>
					<option value="75">75</option>
					<option value="80">80</option>
					<option value="85">85</option>
					<option value="90">90</option>
					<option value="95">95</option>
					<option value="100">100&nbsp;</option>
					</select></td>
			</tr>
			<?php endif; ?>
			<?php if ($cleverness_todo_option['categories'] == '1') : ?>
			<tr>
				<th scope="row"><label for="cleverness_todo_category"><?php _e('Category', 'cleverness-to-do-list') ?></label></th>
				<td><select name="cleverness_todo_category">
					<?php $cats = cleverness_todo_get_cats();
					foreach ( $cats as $cat ) { ?>
					<option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
					<?php } ?>
					</select></td>
			</tr>
			<?php endif; ?>
			<tr>
        		<th scope="row" valign="top"><label for="cleverness_todo_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
        		<td><textarea name="cleverness_todo_description" rows="5" cols="50" id="the_editor"></textarea></td>
			</tr>
			</table>
	   		<?php wp_nonce_field( 'todoadd' ) ?>
			<input type="hidden" name="action" value="addtodo" />
        	<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Add To-Do Item &raquo;', 'cleverness-to-do-list') ?>" /></p>
		</form>
	</div>
	<?php endif; ?>
	<?php
  	}
}

function cleverness_todo_get_users($role) {
      $wp_user_search = new WP_User_Search('', '', $role);
      return $wp_user_search->get_results();
}

/* Add Page under admin and Add Settings Page */
function cleverness_todo_admin_menu() {
	if (function_exists('add_menu_page')) {
		global $userdata, $cleverness_todo_option, $cleverness_todo_cat_page;
   		get_currentuserinfo();

        add_menu_page( __('To-Do List', 'cleverness-to-do-list'), __('To-Do List', 'cleverness-to-do-list'), $cleverness_todo_option['view_capability'], 'cleverness-to-do-list', 'cleverness_todo_subpanel', CTDL_PLUGIN_URL.'/images/cleverness-todo-icon-sm.png');
		if ( $cleverness_todo_option['categories'] == '1' )
			$cleverness_todo_cat_page = add_submenu_page( 'cleverness-to-do-list', __('To-Do List Categories', 'cleverness-to-do-list'), __('Categories', 'cleverness-to-do-list'), $cleverness_todo_option['add_cat_capability'], 'cleverness-to-do-list-cats', 'cleverness_todo_categories');
		add_submenu_page( 'cleverness-to-do-list', __('To-Do List Settings', 'cleverness-to-do-list'), __('Settings', 'cleverness-to-do-list'), 'manage_options', 'cleverness-to-do-list-options', 'cleverness_todo_settings_page');
		add_submenu_page( 'cleverness-to-do-list', __('To-Do List Help', 'cleverness-to-do-list'), __('Help', 'cleverness-to-do-list'), $cleverness_todo_option['view_capability'], 'cleverness-to-do-list-help', 'cleverness_todo_help');
        }
	}

/* Add plugin info to admin footer */
function cleverness_todo_admin_footer() {
	$plugin_data = get_plugin_data( __FILE__ );
	printf(__("%s plugin | Version %s | by %s<br />", 'cleverness-to-do-list'), $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}

/* Add CSS file to admin header */
function cleverness_todo_admin_add_css() {
		$cleverness_style_url = CTDL_PLUGIN_URL . '/css/admin.css';
		$cleverness_style_file = CTDL_PLUGIN_DIR . '/css/admin.css';
        if ( file_exists($cleverness_style_file) ) {
            wp_register_style('cleverness_todo_style_sheet', $cleverness_style_url);
            wp_enqueue_style( 'cleverness_todo_style_sheet');
        }
	}

/* Translation Support */
function cleverness_todo_load_translation_file() {
	$plugin_path = CTDL_BASENAME .'/lang';
	load_plugin_textdomain( 'cleverness-to-do-list', '', $plugin_path );
}

/* Register the options field */
function cleverness_todo_register_settings() {
  register_setting( 'cleverness-todo-settings-group', 'cleverness_todo_settings' );
}

/* Add Settings link to plugin */
function cleverness_add_settings_link($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = CTDL_BASENAME;

	if ($file == $this_plugin){
		$settings_link = '<a href="admin.php?page=cleverness-to-do-list-options">'.__('Settings', 'cleverness-to-do-list').'</a>';
	 	array_unshift($links, $settings_link);
		}
	return $links;
}

// NEED TO ONLY LOAD CSS AND JS ON PLUGIN PAGE

add_filter('plugin_action_links', 'cleverness_add_settings_link', 10, 2 );

/* Add Action Hooks */
if (function_exists('add_action')) {
	add_action('activate_'.CTDL_BASENAME,'cleverness_todo_install');
   	add_action('admin_menu', 'cleverness_todo_admin_menu');
   	add_action('admin_init', 'cleverness_todo_register_settings');
   	add_action('wp_dashboard_setup', 'cleverness_todo_dashboard_setup');
   	add_action('admin_print_styles', 'cleverness_todo_admin_add_css');
  	add_action('widgets_init', 'cleverness_todo_widget');
   	add_action('init', 'cleverness_todo_load_translation_file');
	}

/* JS and Ajax Setup */
// returns various JavaScript vars needed for the scripts
function cleverness_todo_get_js_vars() {
	return array(
	'SUCCESS_MSG' => __('To-Do Deleted.', 'cleverness-to-do-list'),
	'ERROR_MSG' => __('There was a problem performing that action.', 'cleverness-to-do-list'),
	'PERMISSION_MSG' => __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list'),
	'EDIT_TODO' => __('Edit To-Do', 'cleverness-to-do-list'),
	'PUBLIC' => __('Public', 'cleverness-to-do-list'),
	'PRIVATE' => __('Private', 'cleverness-to-do-list'),
	'CONFIRMATION_MSG' => __("You are about to permanently delete the selected item. \n 'Cancel' to stop, 'OK' to delete.", 'cleverness-to-do-list'),
	'NONCE' => wp_create_nonce('cleverness-todo')
	);
}
/* end JS Setup */

add_action( 'admin_init', 'cleverness_todo_init' );

function cleverness_todo_init() {
	wp_register_script( 'cleverness_todo_js', CTDL_PLUGIN_URL.'/js/todos.js', '', 1.0, true );
	wp_register_script( 'cleverness_todo_complete_js', CTDL_PLUGIN_URL.'/js/complete-todo.js', '', 1.0, true );
	add_action('admin_print_scripts', 'cleverness_todo_add_js');
	add_action('wp_ajax_cleverness_todo_complete', 'cleverness_todo_complete_callback');
	add_action('wp_ajax_cleverness_todo_delete', 'cleverness_todo_delete_callback');
	add_action('wp_ajax_cleverness_todo_get', 'cleverness_todo_get_callback');
}

function cleverness_todo_add_js() {
	wp_enqueue_script( 'cleverness_todo_complete_js' );
	wp_enqueue_script( 'cleverness_todo_js' );
	wp_enqueue_script( 'jquery-color' );
	wp_localize_script( 'cleverness_todo_js', 'cltd', cleverness_todo_get_js_vars());
    }

function cleverness_todo_complete_callback() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'complete_todo' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_id = intval($_POST['cleverness_id']);
		$cleverness_status = intval($_POST['cleverness_status']);
		$cleverness_todo_status = cleverness_todo_complete($cleverness_id, $cleverness_status);
	} else {
		$cleverness_todo_status = 2;
		}

	die(); // this is required to return a proper result
}

/* Get To-Do Ajax */
function cleverness_todo_get_callback() {
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add_todo' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_todo = cleverness_todo_get_todo();
	} else {
		$cleverness_todo_status = 2;
		}

	echo json_encode( array( 'cleverness_todo_todotext' => $cleverness_todo->todotext, 'cleverness_todo_priority' => $cleverness_todo->priority ) );

	die(); // this is required to return a proper result
}
/* end Get To-Do Ajax */

/* Delete To-Do Ajax */
function cleverness_todo_delete_callback() {
	check_ajax_referer( 'cleverness-todo' );
	$cleverness_todo_permission = cleverness_todo_user_can( 'todo', 'add_todo' );

	if ( $cleverness_todo_permission === true ) {
		$cleverness_todo_status = cleverness_todo_delete();
	} else {
		$cleverness_todo_status = 2;
		}

	echo $cleverness_todo_status;
	die(); // this is required to return a proper result
}
/* end Delete To-Do Ajax */
?>