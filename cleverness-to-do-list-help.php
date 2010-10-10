<?php
/* Help documentation page */
function cleverness_todo_help() {
?>
<div class="wrap">
<div id="icon-plugins" class="icon32"></div> <h2><?php _e('To-Do List Help', 'cleverness-to-do-list'); ?></h2>
<h3><?php _e('Shortcode Documentation', 'cleverness-to-do-list'); ?></h3>
<p>&#91;todolist&#93;</p>
<p><?php _e('Several options are available:', 'cleverness-to-do-list'); ?></p>
<ul>
<li><strong>title</strong> &#8211; <?php _e('default is no title', 'cleverness-to-do-list'); ?>.</li>
<li><strong>type</strong> &#8211; <?php _e('you can chose <em>list</em> or <em>table</em> view. Default is <em>list</em>', 'cleverness-to-do-list'); ?>.</li>
<li><strong>priorities</strong> &#8211; <?php _e('default is <em>show</em>. Use a blank value to hide (only applies to table view)', 'cleverness-to-do-list'); ?>.</li>
<li><strong>assigned</strong> &#8211; <?php _e('default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list'); ?>.</li>
<li><strong>deadline</strong> &#8211; <?php _e('default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list'); ?>.</li>
<li><strong>progress</strong> &#8211; <?php _e('default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list'); ?>.</li>
<li><strong>addedby</strong> &#8211; <?php _e('default is <em>show</em>. Use a blank value to hide', 'cleverness-to-do-list'); ?>.</li>
<li><strong>completed</strong> &#8211; <?php _e('default is blank. Set to <em>show</em> to display completed items', 'cleverness-to-do-list'); ?>.</li>
<li><strong>completed_title</strong> &#8211; <?php _e('default is no title', 'cleverness-to-do-list'); ?>.</li>
<li><strong>list_type</strong> &#8211; <?php _e('default is <em>ol</em> (ordered list). Use <em>ul</em> to show an unordered list', 'cleverness-to-do-list'); ?>.</li>
<li><strong>category</strong> &#8211; <?php _e('default is <em>all</em>. Use the category ID to show a specific category', 'cleverness-to-do-list'); ?>.</li>
</ul>
<p><strong><?php _e('Example:', 'cleverness-to-do-list'); ?></strong><br />
<?php _e('Table view with the title of Upcoming Articles and showing the progress and who the item was assigned to.', 'cleverness-to-do-list'); ?><br />
&#91;todolist title="Upcoming Articles" type="table" priorities="" deadline="" addedby=""&#93;</p>

<h3><?php _e('Additional Information on Available Permissions', 'cleverness-to-do-list'); ?></h3>

<ul>
<li><strong><?php _e('View To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to view to-do items', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('Complete To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to mark to-do items as completed or uncompleted', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('Add To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to add new to-do items', 'cleverness-to-do-list'); ?>.</li>

<li><strong><?php _e('Edit To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to edit existing to-do items', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('Assign To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to assign to-do items to individual users', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('View All Assigned Tasks Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to view all tasks even if <em>Show Each User Only Their Assigned Tasks</em> is set to <em>Yes</em>', 'cleverness-to-do-list'); ?>.</li>

<li><strong><?php _e('Delete To-Do Item Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to delete individual to-do items', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('Purge To-Do Items Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to purge all the completed to-do items', 'cleverness-to-do-list'); ?>.</li>
<li><strong><?php _e('Add Categories Capability', 'cleverness-to-do-list'); ?></strong> &#8211; <?php _e('This allows the selected capability to add new categories', 'cleverness-to-do-list'); ?>.</li>
</ul>

</div>
<?php
/* Adds information about the plugin on the settings page footer */
add_action( 'in_admin_footer', 'cleverness_todo_admin_footer' );
}
?>