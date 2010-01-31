=== Plugin Name ===
Contributors: elusivelight
Donate link: http://cleverness.org/plugins/to-do-list/
Tags: to-do, to do list, to-do list, list, assign tasks, admin
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: trunk

Manage to-do list items on a individual or group basis with customizable settings.

== Description ==

This plugin provides users with a to-do list feature.

You can configure the plugin to have private to-do lists for each user or for all users to share a to-do list. The shared to-do list has a variety of settings available. You can assign tasks to certain user and have only those tasks viewable to a user. You can also assign different permission levels using capabilities.

A page is added under the Tools menu to manage items and they are also listed on a dashboard widget. You can manage the settings from under the Settings menu.

== Installation ==

1. Upload the folder /cleverness-to-do-list/ to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the settings under the Settings menu
4. Visit To-Do List under the Tools menu

== License ==

This file is part of Cleverness To-Do List.

Cleverness To-Do List is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

Cleverness To-Do List is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <http://www.gnu.org/licenses/>.

== Frequently Asked Questions ==

= Can you explain the permissions in more detail? =

* **View To-Do Item Capability** - This allows the selected capability to view to-do items in the dashboard widget and on the To-Do List page under Tools.
* **Complete To-Do Item Capability** - This allows the selected capability to mark to-do items as completed or uncompleted.
* **Add To-Do Item Capability** - This allows the selected capability to add new to-do items.
* **Edit To-Do Item Capability** - This allows the selected capability to edit existing to-do items.
* **Assign To-Do Item Capability** - This allows the selected capability to assign to-do items to individual users.
* **View All Assigned Tasks Capability** - This allows the selected capability to view all tasks even if *Show Each User Only Their Assigned Tasks* is set to *Yes*.
* **Delete To-Do Item Capability** - This allows the selected capability to delete individual to-do items.
* **Purge To-Do Items Capability** - This allows the selected capability to purge all the completed to-do items.

= What should I do if I find a bug? =

Visit [the plugin website](http://cleverness.org/plugins/to-do-list/) and [leave a comment](http://cleverness.org/plugins/to-do-list/#respond) or [contact me](http://cleverness.org/contact/).

== Screenshots ==

1. Dashboard Widget - Individual Setting
2. Dashboard Widget - Group Setting with Assign Tasks on
3. To-Do List Page - Group Setting with Assign Tasks on
4. To-Do List Page - Group Setting with a minimum permission user, only viewing their assigned items.
5. Editing an Item - Assign Tasks On
6. Settings Page

== Changelog ==

= 1.2 =
* Added ability to check off items from dashboard
* Added uninstall function
* Added group support
* Added settings page
* Added permissions based on capabilities
* Cleaned up code some more
* Added ability to set custom priorities
* Improved security
* Added translation support

= 1.1 =
* Enabled the plugin to work from inside a directory

= 1.0 =
* Improved the security of the plugin
* Updated the formatting to match the admin interface
* Cleaned up the code
* Fixed to work in WordPress 2.8

== Upgrade Notice ==

= 1.2 =
Major changes to plugin

== Credits ==

This plugin was originally from Abstract Dimensions (site no longer available) with a patch to display the list in the dashboard by WordPress by Example (site also no longer available). It was abandoned prior to WordPress 2.7.