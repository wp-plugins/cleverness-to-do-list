<?php
/*
Plugin Name: Cleverness To-Do List
Version: 1.2
Description: Manage to-do list items on a individual or group basis. Adds a page under the Tools menu and a dashboard widget.
Author: C.M. Kendrick
Author URI: http://cleverness.org
Plugin URI: http://cleverness.org/plugins/to-do-list/
*/

/*
Based on the ToDo plugin by Abstract Dimensions with a patch by WordPress by Example.
*/

global $wp_version;

$exit_msg = __('To-Do List requires WordPress 2.8 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update.</a>', 'cleverness-to-do-list');

if (version_compare($wp_version, "2.8", "<")) {
	exit($exit_msg);
  	}

require ABSPATH . WPINC . '/pluggable.php';

get_currentuserinfo();

if (current_user_can('manage_options'))
	include 'cleverness-to-do-list-options.php';

$atd_option = get_option('cleverness_todo_settings');

$wpvarstoreset = array('action','todo');
for ($i=0; $i<count($wpvarstoreset); $i += 1) {
    $wpvar = $wpvarstoreset[$i];
    if (!isset($$wpvar)) {
        if (empty($_POST["$wpvar"])) {
            if (empty($_GET["$wpvar"])) {
                $$wpvar = '';
            } else {
                $$wpvar = $_GET["$wpvar"];
            }
        } else {
            $$wpvar = $_POST["$wpvar"];
        }
    }
}

/* Location used for redirect after command is executed */
$location = get_settings('siteurl') . '/wp-admin/tools.php?page=cleverness-to-do-list';

switch($action) {
case 'addtodo':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['add_capability']) ) {
		if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'todoadd') ) die('Security check');
		$todotext = attribute_escape($_POST['atd_description']);
		$priority = attribute_escape($_POST['atd_priority']);
		$assign = attribute_escape($_POST['atd_assign']);
		atd_insert($todotext, $priority, $assign);
    	header('Location: '.$location.'&message=1');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'trashtd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['delete_capability']) ) {
		$id = attribute_escape($_GET['id']);
		atd_delete($id);
		header('Location: '.$location.'&message=3');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'comptd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['complete_capability']) ) {
		$id = attribute_escape($_GET['id']);
		atd_complete($id, '1');
		header('Location: '.$location.'&message=2');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'uncomptd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['complete_capability']) ) {
		$id = attribute_escape($_GET['id']);
		atd_complete($id, '0');
		header('Location: '.$location.'&message=7');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'purgetd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['purge_capability']) ) {
		atd_purge();
		header('Location: '.$location.'&message=6');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'setuptd':
	atd_install();
	header('Location: '.$location.'&message=5');
	break;

case 'edittd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['edit_capability']) ) {
		$id = attribute_escape($_GET['id']);
		header('Location: '.$location.'&action=tdefd&id='.$id.'&');
	} else {
		header('Location: '.$location.'&message=8');
		}
	break;

case 'updatetd':
	if ( $atd_option['list_view'] == '0' || current_user_can($atd_option['edit_capability']) ) {
		if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'todoupdate') ) die('Security check');
		$id = attribute_escape($_POST['id']);
		$todotext = attribute_escape($_POST['atd_description']);
		$priority = attribute_escape($_POST['atd_priority']);
		$assign = attribute_escape($_POST['atd_assign']);
		atd_update($id, $priority, $todotext, $assign);
    	header('Location: '.$location.'&message=4');
	} else {
		header('Location: '.$location.'&message=8');
		}
}

/* Insert new to-do item into the database */
function atd_insert($todotext, $priority, $assign) {
	global $wpdb, $userdata, $atd_option;
   	get_currentuserinfo();

	if (current_user_can($atd_option['add_capability'])) {
  	 	$table_name = $wpdb->prefix . 'todolist';
   		$results = $wpdb->insert( $table_name, array( 'author' => $userdata->ID, 'status' => 0, 'priority' => $priority, 'todotext' => $todotext, 'assign' => $assign ) );
		}
	}

/* Update to-do list item */
function atd_update($id, $priority, $todotext, $assign) {
   	global $wpdb, $userdata, $atd_option;
   	get_currentuserinfo();

   	if (current_user_can($atd_option['edit_capability'])) {
		$table_name = $wpdb->prefix . 'todolist';
   		$results = $wpdb->update( $table_name, array( 'priority' => $priority, 'todotext' => $todotext, 'assign' => $assign ), array( 'author' => $userdata->ID, 'id' => $id) );
   		}
	}

/* Delete to-do list item */
function atd_delete($id) {
   	global $wpdb, $userdata, $atd_option;
   	$table_name = $wpdb->prefix . 'todolist';
   	if (current_user_can($atd_option['delete_capability'])) {
   		$delete = "DELETE FROM ".$table_name." WHERE id = '".$id."' AND author = '".$userdata->ID."'";
   		$results = $wpdb->query( $delete );
   	   	}
	}

/* Mark to-do list item as completed */
function atd_complete($id, $status) {
  	 global $wpdb, $atd_option;
   	 $table_name = $wpdb->prefix . 'todolist';
   	 if (current_user_can($atd_option['complete_capability'])) {
		$results = $wpdb->update( $table_name, array( 'status' => $status), array( 'id' => $id) );
		}
	}

/* Get to-do list item */
function atd_get_todo($id) {
   	global $wpdb, $userdata, $atd_option;
   	get_currentuserinfo();

   	$table_name = $wpdb->prefix . 'todolist';
   	if (current_user_can($atd_option['edit_capability'])) {
   		$edit = "SELECT * FROM ".$table_name." WHERE id = '".$id."' LIMIT 1";
   		$result = $wpdb->get_row( $edit );
   		return $result;
   		}
	}

/* Delete all completed to-do list items */
function atd_purge() {
   	global $wpdb, $userdata, $atd_option;
   	get_currentuserinfo();

   	$table_name = $wpdb->prefix . 'todolist';
   	if (current_user_can($atd_option['purge_capability'])) {
   		if ( $atd_option['list_view'] == '0' )
   			$purge = "DELETE FROM ".$table_name." WHERE status = '1' AND author = '".$userdata->ID."'";
	   	elseif ( $atd_option['list_view'] == '1' )
			$purge = "DELETE FROM ".$table_name." WHERE status = '1'";
   		$results = $wpdb->query( $purge );
   		}
	}

/* Create database table and add default options */
function atd_install () {
   	global $wpdb, $userdata;
   	get_currentuserinfo();

	if (current_user_can('install_plugins')) {
   	$table_name = $wpdb->prefix . 'todolist';

   	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
   		$sql = "CREATE TABLE ".$table_name." (
	      id bigint(20) NOT NULL AUTO_INCREMENT,
	      author bigint(20) NOT NULL,
	      status tinyint(1) DEFAULT '0' NOT NULL,
	      priority tinyint(1) NOT NULL,
          todotext text NOT NULL,
		  assign varchar(20),
	      UNIQUE KEY id (id)
	    );";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   		dbDelta($sql);
   		$welcome_text = __('Add your first To-Do List item', 'cleverness-to-do-list');
   		$results = $wpdb->insert( $table_name, array( 'author' => $userdata->ID, 'status' => 0, 'priority' => 1, 'todotext' => $welcome_text ) );
   		}

	$new_options = array(
		'list_view' => '0',
		'dashboard_author' => '0',
		'todo_author' => '0',
		'assign' => '1',
		'show_only_assigned' => '1',
		'view_capability' => 'publish_posts',
		'add_capability' => 'publish_posts',
		'edit_capability' => 'publish_posts',
		'delete_capability' => 'manage_options',
		'purge_capability' => 'manage_options',
		'complete_capablity' => 'publish_posts',
		'assign_capability' => 'manage_options',
		'view_all_assigned_capability' => 'manage_options',
		'dashboard_number' => '10',
		'priority_0' => 'Important',
		'priority_1' => 'Normal',
		'priority_2' => 'Low'
	);
	add_option( 'cleverness_todo_settings', $new_options );
	}

	}

/* Create admin page */
function atd_todo_subpanel() {
   	global $wpdb, $userdata, $atd_option;
   	get_currentuserinfo();

   	$table_name = $wpdb->prefix . 'todolist';
   	$priority = array(0 => $atd_option['priority_0'] , 1 => $atd_option['priority_1'], 2 => $atd_option['priority_2']);

	$messages[1] = __('New To-Do item has been added.', 'cleverness-to-do-list');
	$messages[2] = __('To-Do item has been marked completed.', 'cleverness-to-do-list');
	$messages[3] = __('To-Do item has been deleted.', 'cleverness-to-do-list');
	$messages[4] = __('To-Do item has been updated.', 'cleverness-to-do-list');
   	$messages[5] = __('To-Do database table has been installed.', 'cleverness-to-do-list');
	$messages[6] = __('Completed To-Do items have been deleted.', 'cleverness-to-do-list');
	$messages[7] = __('To-Do item has been marked uncompleted.', 'cleverness-to-do-list');
	$messages[8] = __('You do not have sufficient privileges to do that.', 'cleverness-to-do-list');
	?>

	<?php if (isset($_GET['message'])) : ?>
		<div id="message" class="updated fade"><p><?php echo $messages[$_GET['message']]; ?></p></div>
	<?php endif; ?>

	<?php
	/* Display this section if editing an existing to-do item */
	if ($_GET['action'] == 'tdefd') {
    	$id = $_GET['id'];
    	$todo = atd_get_todo($id);
	?>

	<div class="wrap">
 		<div id="icon-tools" class="icon32"></div> <h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
 		<h3><?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?></h3>
 		<form name="edittd" action="tools.php?page=cleverness-to-do-list" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="atd_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
					<select name="atd_priority">
					<option value="0" <?php if ($todo->priority == 0) { echo "selected"; } ?>><?php echo $atd_option['priority_0']; ?>&nbsp;</option>
					<option value="1" <?php if ($todo->priority == 1) { echo "selected"; } ?>><?php echo $atd_option['priority_1']; ?></option>
					<option value="2" <?php if ($todo->priority == 2) { echo "selected"; } ?>><?php echo $atd_option['priority_2']; ?></option>
					</select>
					<input type="hidden" name="id" value="<?php echo $todo->id ?>" />
				</td>
			</tr>
			<?php if ($atd_option['list_view'] == '1' && $atd_option['assign'] == '0' && current_user_can($atd_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="atd_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td><?php wp_dropdown_users('show_option_none=None&name=atd_assign&selected='.$todo->assign); ?></td>
			</tr>
			<?php endif; ?>
	   		<tr>
				<th scope="row" valign="top"><label for="atd_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
				<td><textarea name="atd_description" rows="5" cols="50"><?php echo wp_specialchars($todo->todotext, 1); ?></textarea></td>
			</tr>
			</table>
			<?php wp_nonce_field( 'todoupdate' ) ?>
			<input type="hidden" name="action" value="updatetd" />
	  		<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Edit To-Do Item', 'cleverness-to-do-list') ?> &raquo;" /></p>
 		</form>
 		<p><a href="tools.php?page=cleverness-to-do-list"><?php _e('&laquo; Return to To-Do List', 'cleverness-to-do-list'); ?></a></p>
	</div>

	<?php
	} else {
	/* Display the to-do list items */
	?>

	<div class="wrap">
   		<div id="icon-tools" class="icon32"></div>	<h2><?php _e('To-Do List', 'cleverness-to-do-list'); ?></h2>
		<h3><?php _e('To-Do Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($atd_option['add_capability'])) : ?>
			(<a href="#addtd"><?php _e('Add New Item', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
		<table id="todo-list" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	  		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	  		<?php if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($atd_option['edit_capability'])) : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>
		<?php
		if ( $atd_option['list_view'] == '0' )
			$sql = "SELECT id, status, todotext, priority FROM $table_name WHERE status = 0 AND author = $userdata->ID ORDER BY priority";
		elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' && (current_user_can($atd_option['view_all_assigned_capability'])) )
			$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 0 ORDER BY priority";
		elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' )
		   	$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 0 AND assign = $userdata->ID ORDER BY priority";
   		elseif ( $atd_option['list_view'] == '1' )
			$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 0 ORDER BY priority";

   		$results = $wpdb->get_results($sql);
   		if ($results) {
	   		foreach ($results as $result) {
		   		$class = ('alternate' == $class) ? '' : 'alternate';
		   		$prstr = $priority[ $result->priority ];
		   		$priority_class = '';
		   		$user_info = get_userdata($result->author);
		   		if ($result->priority == '0') $priority_class = ' todo-important';
				if ($result->priority == '2') $priority_class = ' todo-low';
				$edit = '';
				if (current_user_can($atd_option['edit_capability']))
		  			$edit = '<a href="tools.php?page=cleverness-to-do-list&amp;action=edittd&amp;id='.$result->id.'&amp;noheader&amp;message=3" class="edit">'.__('Edit', 'cleverness-to-do-list').'</a>';
				if (current_user_can($atd_option['delete_capability']))
					$edit .= ' | <a href="tools.php?page=cleverness-to-do-list&amp;action=trashtd&amp;id='.$result->id.'&amp;noheader&amp;message=3" class="delete">'.__('Delete', 'cleverness-to-do-list').'</a>';
		   		echo '<tr id="atd-'.$result->id.'" class="'.$class.$priority_class.'">
			   	<td><input type="checkbox" id="td-'.$result->id.'" onclick="window.location = \'tools.php?page=cleverness-to-do-list&amp;action=comptd&amp;id='.$result->id.'&amp;noheader&amp;message=2\';" />&nbsp;'.$result->todotext.'</td>
			   	<td>'.$prstr.'</td>';
				if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) {
					$assign_user = '';
					if ( $result->assign != '-1' )
						$assign_user = get_userdata($result->assign)->display_name;
					echo '<td>'.$assign_user.'</td>';
					}
		   		if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' )
		   			echo '<td>'.$user_info->display_name.'</td>';
		   		if (current_user_can($atd_option['edit_capability']))
					echo '<td>'.$edit.'</td></tr>';
	   		}
   		} else {
	   		echo '<tr><td ';
	   		$colspan = 2;
	   		if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) $colspan += 1;
			if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' ) $colspan += 1;
			if ( current_user_can($atd_option['edit_capability']) ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	   		echo '>'.__('There is nothing to do...', 'cleverness-to-do-list').'</td></tr>';
   			}
		?>
		</table>
	</div>

	<div class="wrap">
		<h3><?php _e('Completed Items', 'cleverness-to-do-list'); ?>
		<?php if (current_user_can($atd_option['purge_capability'])) : ?>
			(<a href="tools.php?page=cleverness-to-do-list&amp;action=purgetd&amp;noheader&amp;message=6"><?php _e('Delete All', 'cleverness-to-do-list'); ?></a>)
		<?php endif; ?>
		</h3>
		<table id="todo-list-completed" class="widefat">
		<thead>
		<tr>
	   		<th><?php _e('Item', 'cleverness-to-do-list'); ?></th>
	   		<th><?php _e('Priority', 'cleverness-to-do-list'); ?></th>
			<?php if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) : ?><th><?php _e('Assigned To', 'cleverness-to-do-list'); ?></th><?php endif; ?>
	   		<?php if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' ) : ?><th><?php _e('Added By', 'cleverness-to-do-list'); ?></th><?php endif; ?>
       		<?php if (current_user_can($atd_option['delete_capability'])) : ?><th><?php _e('Action', 'cleverness-to-do-list'); ?></th><?php endif; ?>
    	</tr>
		</thead>
		<?php
		if ( $atd_option['list_view'] == '0' )
			$sql = "SELECT id, status, todotext, priority FROM $table_name WHERE status = 1 AND author = $userdata->ID ORDER BY priority";
		elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' && (current_user_can($atd_option['view_all_assigned_capability'])) )
			$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 1 ORDER BY priority";
		elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' )
			$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 1 AND assign = $userdata->ID ORDER BY priority";
		elseif ( $atd_option['list_view'] == '1' )
	   		$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 1 ORDER BY priority";
   		$results = $wpdb->get_results($sql);
   		if ($results) {
	   		foreach ($results as $result) {
		   		$class = ('alternate' == $class) ? '' : 'alternate';
		   		$prstr = $priority[ $result->priority ];
		   		$user_info = get_userdata($result->author);
				$edit = '';
				if (current_user_can($atd_option['delete_capability']))
		   			$edit = '<a href="tools.php?page=cleverness-to-do-list&amp;action=trashtd&amp;id='.$result->id.'&amp;noheader&amp;message=3" class="delete">'.__('Delete', 'cleverness-to-do-list').'</a>';
		   		echo '<tr id="atd-'.$result->id.'" class="'.$class.'">
			   	<td><input type="checkbox" id="td-'.$result->id.'" checked="checked" onclick="window.location = \'tools.php?page=cleverness-to-do-list&amp;action=uncomptd&amp;id='.$result->id.'&amp;noheader&amp;message=2\';" />&nbsp;'.$result->todotext.'</td>
			   	<td>'.$prstr.'</td>';
				if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) {
					$assign_user = '';
					if ( $result->assign != '-1' )
						$assign_user = get_userdata($result->assign)->display_name;
					echo '<td>'.$assign_user.'</td>';
					}
		   		if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' )
		   			echo '<td>'.$user_info->display_name.'</td>';
		  		if (current_user_can($atd_option['delete_capability']))
					 echo '<td>'.$edit.'</td>
			 	</tr>';
	  	 		}
   		} else {
	  		echo '<tr><td ';
			$colspan = 2;
	   		if ( $atd_option['list_view'] == '1' && $atd_option['assign'] == '0' ) $colspan += 1;
			if ( $atd_option['list_view'] == '1' && $atd_option['todo_author'] == '0' ) $colspan += 1;
			if ( current_user_can($atd_option['delete_capability']) ) $colspan += 1;
			echo 'colspan="'.$colspan.'"';
	  	 	echo '>'.__('There are no completed items', 'cleverness-to-do-list').'</td></tr>';
   		}
		?>
   		</table>
	</div>

	<?php if (current_user_can($atd_option['add_capability'])) : ?>
	<div class="wrap">
   	 	<h3><?php _e('Add New To-Do Item', 'cleverness-to-do-list') ?></h3>
    	<form name="addtd" id="addtd" action="tools.php?page=cleverness-to-do-list" method="post">
	  		<table class="form-table">
			<tr>
		  		<th scope="row"><label for="atd_priority"><?php _e('Priority', 'cleverness-to-do-list') ?></label></th>
		  		<td>
        			<select name="atd_priority">
       	 				<option value="0"><?php echo $atd_option['priority_0']; ?>&nbsp;</option>
        				<option value="1" selected="selected"><?php echo $atd_option['priority_1']; ?></option>
       	 		   		<option value="2"><?php echo $atd_option['priority_2']; ?></option>
        			</select>
		  		</td>
			</tr>
			<?php if ($atd_option['list_view'] == '1' && $atd_option['assign'] == '0' && current_user_can($atd_option['assign_capability'])) : ?>
			<tr>
		  		<th scope="row"><label for="atd_assign"><?php _e('Assign To', 'cleverness-to-do-list') ?></label></th>
		  		<td><?php wp_dropdown_users('show_option_none=None&name=atd_assign'); ?></td>
			</tr>
			<?php endif; ?>
			<tr>
        		<th scope="row" valign="top"><label for="atd_description"><?php _e('To-Do', 'cleverness-to-do-list') ?></label></th>
        		<td><textarea name="atd_description" rows="5" cols="50"></textarea></td>
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

/* Display Dashboard Widget */
function atd_todo_in_activity_box() {
   	global $wpdb, $userdata, $atd_option;
	get_currentuserinfo();

	$table_name = $wpdb->prefix . 'todolist';
	$number = $atd_option['dashboard_number'];
	if ( $atd_option['list_view'] == '0' )
		$sql = "SELECT id, todotext, priority FROM $table_name WHERE status = 0 AND author = $userdata->ID ORDER BY priority LIMIT $number";
	elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' && (current_user_can($atd_option['view_all_assigned_capability'])) )
		$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 0 ORDER BY priority LIMIT $number";
	elseif ( $atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' )
		$sql = "SELECT id, todotext, priority, author FROM $table_name WHERE status = 0 AND assign = $userdata->ID ORDER BY priority LIMIT $number";
	elseif ( $atd_option['list_view'] == '1' )
		$sql = "SELECT id, todotext, priority, author, assign FROM $table_name WHERE status = 0 ORDER BY priority LIMIT $number";
	$results = $wpdb->get_results($sql);
	if ($results) {
		foreach ($results as $result) {
			$user_info = get_userdata($result->author);
			$priority_class = '';
		   	if ($result->priority == '0') $priority_class = ' class="todo-important"';
			if ($result->priority == '2') $priority_class = ' class="todo-low"';
			echo '<p><input type="checkbox" id="td-'.$result->id.'" onclick="window.location = \'tools.php?page=cleverness-to-do-list&amp;action=comptd&amp;id='.$result->id.'&amp;noheader&amp;message=2\';" /> <span'.$priority_class.'>'.$result->todotext.'</span>';
			if ( ($atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '0' && (current_user_can($atd_option['view_all_assigned_capability']))) ||  ($atd_option['list_view'] == '1' && $atd_option['show_only_assigned'] == '1') && $atd_option['assign'] == '0') {
				$assign_user = '';
				if ( $result->assign != '-1' )
					$assign_user = get_userdata($result->assign)->display_name;
				echo ' <small>['.__('assigned to', 'cleverness-to-do-list').' '.$assign_user.']</small>';
			}
			if ( $atd_option['list_view'] == '1' && $atd_option['dashboard_author'] == '0' )
				echo ' <small>- '.__('added by', 'cleverness-to-do-list').' '.$user_info->display_name.'</small>';
			if (current_user_can($atd_option['edit_capability']))
		   		echo ' <small>(<a href="tools.php?page=cleverness-to-do-list&amp;action=edittd&amp;id='. $result->id . '&amp;noheader&amp;message=3">'. __('Edit', 'cleverness-to-do-list') . '</a>)</small>';
			echo '</p>';
			}
	} else {
		echo '<p>'.__('No items to do.', 'cleverness-to-do-list').'</p>';
		}
		if (current_user_can($atd_option['add_capability']))
			echo '<p style="text-align: right">'. '<a href="tools.php?page=cleverness-to-do-list#addtd">'. __('New To-Do Item &raquo;', 'cleverness-to-do-list').'</a></p></div>';
	}


/* Add Page under Tools and Add Settings Page */
function atd_admin_menu() {
	if (function_exists('add_submenu_page')) {
		global $userdata, $atd_option;
   		get_currentuserinfo();
        add_management_page( __('To-Do List', 'cleverness-to-do-list'), __('To-Do List', 'cleverness-to-do-list'), $atd_option['view_capability'], 'cleverness-to-do-list', 'atd_todo_subpanel');
		add_options_page( __('To-Do List', 'cleverness-to-do-list'), __('To-Do List', 'cleverness-to-do-list'), 'manage_options', 'cleverness-to-do-list', 'atd_settings_page');
        }
	}

/* Add plugin info to admin footer */
function atd_admin_footer() {
	$plugin_data = get_plugin_data( __FILE__ );
	printf('%1$s plugin | Version %2$s | by %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}


/* Add Dashboard Widget */
function atd_dashboard_setup() {
	global $userdata, $atd_option;
   	get_currentuserinfo();

   	if (current_user_can($atd_option['view_capability'])) {
		wp_add_dashboard_widget('atd_todo', __( 'To-Do List', 'cleverness-to-do-list' ) . ' <a href="tools.php?page=cleverness-to-do-list">'. __('&raquo;', 'cleverness-to-do-list').'</a>', 'atd_todo_in_activity_box' );
		}
	}

/* Add CSS file to admin header */
function atd_admin_add_css() {
	$siteurl = get_option('siteurl');
	$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/admin.css';
	echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}

/* Translation Support */
function atd_load_translation_file() {
	$plugin_path = plugin_basename( dirname( __FILE__ ) .'/lang' );
	load_plugin_textdomain( 'cleverness-to-do-list', '', $plugin_path );
}

/* Register the options field */
function atd_register_settings() {
  register_setting( 'atd-settings-group', 'cleverness_todo_settings' );
}

/* Add Action Hooks */
if (function_exists('add_action')) {
 	add_action('activate_'.plugin_basename(__FILE__),'atd_install');
  	add_action('admin_menu', 'atd_admin_menu');
	add_action('admin_init', 'atd_register_settings');
	add_action('wp_dashboard_setup', 'atd_dashboard_setup');
	add_action('admin_head', 'atd_admin_add_css');
	add_action('init', 'atd_load_translation_file');
	}
?>