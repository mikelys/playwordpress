=== CLUEVO LMS, E-Learning Platform ===
Contributors: cluevo
Donate link: https://cluevo.at/donate/
Tags: scorm, lms, learning management system, cluevo, e-learning, learning, teaching, trainer, video, audio, lernen, lehrer, education, bildung, elearning, articulate, rise, tutorial, tutorials, video tutorials, video tutorial, podcast, member, member area, mitglieder, mitgliederbereich, membership, mitgliedschaft
Stable tag: 1.5.2
Requires at least: 4.6
Tested up to: 5.4
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transforms your WordPress into a powerful Learning Management System. Organize video tutorials, podcasts and interactive SCORM courses with quizzes and more.

== Description ==

= Introduction =
Welcome to the CLUEVO Learning Management System for WordPress. Our LMS allows you to add SCORM e-learning modules, video tutorials, podcasts and other media to your WordPress site. That Content can be organized into courses, chapters and modules and you can easily manage the permissions for different users and groups.

= SCORM =
We currently support SCORM 2004 4th edition and have recently added initial support for SCORM 1.2. Other 2004 editions may work but are not officially supported. We are hard at work to provide better support for more versions of the standard. If you have any suggestions on what standards to support please don't hesitate to get in touch with us via the support Forum.

= Video/Audio =
Currently many File Formats like mp3, wav, mp4 and webm are supported. With our free oEmbed extension you can also add and organize videos from Youtube, twitch and other Streaming services.

= Learning Structure =
The LMS consists of different courses that in turn contain chapters that contain modules. The first thing you'll want to do is upload a SCORM module. To do this use the uploader on the Learning Management page in the modules tab. Once you have uploaded one (or more!) modules you can start creating your learning structure. Create some courses and add chapters and modules. 

= User Management =
CLUEVO LMS gives you the ability to set permissions for each level of the learning tree. You can assign users to groups and set permissions for groups or just individual users. Each element of the learning tree can have one of three access levels:

0: No access. Items won't show up anyware for this user/group
1: Visible. Items will be visible for a user/group but cannot otherwise be accessed
2: Open. The user/group has full access to this element.

Hint: As a user with administrative capabilities you have full access to all elements by default.

= Reports =
The reports page gives you an overview on the progress your users have made. You can also view the different SCORM parameters.

= Competence =
The competence system allows you to define competence areas that consist of different competences. You can then set which modules teach which competences and how much of a competence a module covers.

An example could be that you have a competence area named Backoffice that consists of the following competences:
  
* Excel
* Word
* Outlook

You could have various modules that teach each of these competences (to different degrees) and that way get an overview what competences your users have learned.

= Settings =
On the settings page you can set various general options like how modules are displayed, the maximum level, titles etc.

= Feedback =
If you have any feedback or feature requests please do not hesitate to contact us via the support Forum (https://wordpress.org/support/plugin/cluevo-lms/).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cluevo` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Upload your SCORM modules and create a learning structure through the CLUEVO settings pages
4. Optionally set some permissions
5. Start learning (or teaching ;) )!

== Changelog ==
= 1.5.1 =
* Added temporary fix for a notice when checking the server version with some mariadb servers

= 1.5.0 =
* Completely rebuilt the settings page
* Fixed a bug that caused groups to not load due to collation issues
* Made the empty element message box customizable
* Items can now be made into links. Assigned modules have no effect, instead users are sent to the entered link
* The login page can now be enabled/disabled and configured. You can use the cluevo login page, the WordPress default login page or select a different page alltogether
* Improved the MySQL server version warning, it should now no longer display for MariaDB users
* Fixed a bug that caused dependencies to reset when manipulating the learning tree
* Fixed a bug that caused the has-dependencies icon to not display
* Fixed a bug in the permissions system that sometimes failed to get the effective permissions for users with multiple group memberships
* Fixed an error with the evaluation of a user's completed content

= 1.4.7 =
* Fixed a bug that caused items a user shouldn't be able to see to show up when using the shortcode

= 1.4.6 =
* Added a basic security feature that protects modules from being accessed from outside the site. Can be enabled in the general settings.
* Added an option force modules to load via https regardless of the site url
* Added new shortcode arguments to display items as tiles, rows or links
* Added an indicator to items on the tree admin page that flags items with dependencies
* Fixed a bug that caused items/modules to not properly update their dependencies
* Fixed a bug that prevented resetting permissions
* Added the student_id parameter to the set of default scorm parameters

= 1.4.5 =
* Fixed a bug that prevented modules from displaying when using the shortcode
* Some minor styling changes

= 1.4.4 =
* Resolve effective permissions for users in permission view
* Build a group cache on init to speed up user loading in permission view
* Added button to the permission view to completely remove a permission entry
* Fixed a bug caused by translating internal module types that cause modules to not load on the frontend side of things
* Improved support for older SCORM 1.2 modules
* Added an option to keep data after uninstall. The default is that your data now persists after uninstalling the plugin, so be sure to disable if you want a complete uninstall.

= 1.4.3 =
* Fixed some php warnings
* Removed deprecated session start that caused some errors during the WordPress health check

= 1.4.2 =
* Added a page reload when a completed module is closed to refresh the displayed items for updated dependencies
* Fixed dependency system. Module dependencies should now work again.
* Fixed a bug that caused new attempts to be created when a module was completed and then closed
* Fixed a bug that cause module progress to not update
* Fixed queries that queried the WordPress users table to work with sites that have the custom user table consts set
* Changed the way module progress is handled. New attempts are no now longer automatically started, instead the user get's a prompt where they can select if they want to resume the old attempt or start a new one
* Added the extension compatibility table to plugin manager. This will show you what extensions you have installed and inform you about their compatibility
* Replaced all german localized strings with the english ones to make it easier for people to translate
* Display error messages if module installation fails
* Check http status code before attempting to download modules from urls
* Before attempting to install zips as scorm modules, check if the module has a manifest present
* Check the size of selected files in the uploader and block upload if the size is over the max. upload size
* Did some housecleaning, moved some functions to properly named files
* Added a blacklist of filenames and extension to the uploader to prevent the upload of malicious code (also checks inside zips)
* Fixed sco selection
* Optimized filesizes of placeholder images, they really didn't need to be in 4k...
* Fixed lookup of users completed modules and the resulting dependency check

= 1.4.1 =
* Added a missing permission callback to the upload process
* Prepared for future extensions

= 1.4.0 =
* Modules can now be added at each level of the learning tree, you are no longer required to create courses and chapters to create a module entry
* Added a dialog to assign a modules to tree items. You can search and filter the available modules from this dialog.
* Added a tile for available extensions to the add module dialog
* The changed the name of the api settings variable to avoid naming conflicts with other plugins
* Added a popup for displaying error messages like permission denied errors when trying to open modules the user has no permission to access
* Meta icons on tiles no longer scale up when hovering an item
* Fixed some missing localized strings
* Added dashicons to frontend so breadcrumbs show their separators
* Fixed a bug that caused permissions to not work correctly for not-logged in users occasionally
* Fixed a permission bug when displaying items on pages via shortcodes
* Disabled creation of guest user ids for module progress, progress is now only stored for logged in users
* Fixed a bug that caused font-awesome icons to no longer work on certain themes
* Reworked guest permission handling
* Only display missing module message if a module page has no content if no module is assigned
* Separated breadcrumbs and list display switch. Breadcrumbs are now always at the start of the page

= 1.3.1 =
* There should now appear a notice to update the database if an update is necessary

= 1.3.0 =
* Added a new module upload ui
* Modules can now be renamed
* Any API calls now work regardless of permalink settings
* Added support for e-mail groups. Any group that starts with an '@' sign is an e-mail group. All users that have an e-mail with a matching domain are automatically members of this group.
* Elements in the learning tree can now activated and deactivated. Post status changes accordingly to published or draft.
* The display mode can now be set individually for each element in the learning tree
* Added some customization options to the lightbox that can be set individually for each module in the tree
* Max. possible upload filesize is now diplayed in the module file selection
* Pressing the return key while editing the names of elements in the learning tree now no longer creates new items
* WordPress posts of learning tree items now open in new windows/tabs

= 1.2.2 =
* Fixed a bug that prevented modules from loading

= 1.2.1 =
* Improved support for SCORM 1.2
* Progress for SCORM 1.2 modules should now be correctly determined and stored
* Supress the select sco dialog if only one sco is available
* Only ever make one scorm api (2004 OR 1.2) available to modules
* Added SCORM 1.2 support to the progress table
* Added a field to store a modules scorm version

= 1.2.0 =
* Added initial version of SCORM 1.2 support. Please report any bugs via the support forum.
* Added support for SCORM modules with multiple SCOs, when a module has more than one sco you can now select which sco to launch
* Added a hook to handle non-file/non-url module installs
* Added a button to reset all dependencies on a tree
* Temporarily removed module dependencies from courses/chapters while we come up with a better system
* Enabled comment support for CLUEVO LMS post types, this enables your users to leave comments on your courses, chapter etc. To enable comments for an item open the WordPress post and check the comment checkbox.
* Fixed a bug that caused invalid paths by converting complete module paths to lowercase
* Fixed the module dependency display in the tree view
* Items can now no longer depend on themselves
* The dependency list now refreshes when you select another module via the dropdown
* Added the lightbox active class to the html element to fix scrollbars on the html root element
* Fixed a bug that deleted too many metadata pages when deleting a module which caused the tree to become broken

= 1.1.1 =
* Removed empty-module class from non-module items in frontend
* Hover effect for frontend tiles (expanding corner)
* Styling fixes
* Added missing text-domains
* Improved breadcrumb styling

= 1.1 =
* Added an additional button to the save the learning tree at the bottom of the page
* Added a new listing style to the frontend (display items as rows)
* Modules are now stored in a subdirectory for each module type, this means a video module should no longer overwrite a audio or scorm module
* Fixed inconsistent behaviour of save buttons in the competence and competence area pages
* Changed the way you select if a module is to be installed by file upload or url
* Migrated modules from the root of the module directories to each module type
* Added a version check before running the plugin
* Now supports php versions >= 5.6
* Module tree items now support not assigning a module
* Module tree items can now have their display mode set individually
* Made the add course button on the tree page more prominent if no courses have been added yet
* Removed a bug that caused emojis to not work if the plugin is activated
* Fixed the maximum height of modals on the admin pages
* Added hooks to handle module installs in preparation for upcoming extensions
* Added a hook to handle saving progress for upcoming extensions
* Upated localization files
* Updated styling of the lms admin page
* Added an indicator to the lms admin page to show where new items will be inserted
* Updated the icons on the lms tree admin page
* Disabled module download button for modules that can't be downloaded
* Post thumbnails are now supported for cluevo posts even if the theme does not support them
* The edit metadata button should now show up for trees
* Javascript and CSS files are now registered with the current cluevo version for better cache invalidation
* Renamed competence areas to competence groups
* Added basic help tabs to all CLUEVO admin pages
* Added a confirmation prompt before deleting tree items
* Fixed the on-page module navigation when viewing a modules post
* Added breadcrumb system to lms pages
* Unified button styles
* Added a colored indicator to lms item tiles in the frontend
* You can now install a demo course with demo modules from our homepage

= 1.0 =
* This is the initial release!

== Frequently Asked Questions ==

= My module fails to upload =
Please check your PHP max. script execution time and increase it if necessary. Your hosting provider can adjust this value for you.

= I can't see my content =

Make sure you are logged in or that you have set the permissions for the guest group accordingly.

= How do I access my learning content? =

You can add a link to the course index page through the menu editor or add a shortcode to any page where you want to display cluevo content

= How to I get a shortcode? =

You can copy the shortcode by using the [s] buttons for each element on the learning management page or by clicking on the item id that appears when you move your mouse over an item.
The shortcode supports two parameters: row and tile. By using these parameters you can set how the item is displayed on the page. You can also display elements as links by using the shortcode style [cluevo item="x"]This is a link[/cluevo]

= Can i display my modules on arbitrary pages? =

Absolutely! Just an items shortcode with the [s] button on the tree page and insert the shortcode where you want to display your module

= Do you take feature requests? = 

Absolutely! Do not hesitate to contact us via the support Forum (https://wordpress.org/support/plugin/cluevo-lms/) or send your requests to wp@cluevo.at! For more expansive features we're happy to get back to you with a quote.

== Upgrade Notice ==
= 1.5.1 =
Minor temp. bugfix for mariadb

= 1.5.0 =
Revamped the settngs page

= 1.4.7 =
Bugfixes

= 1.4.6 =
Better shortcodes, bugfixes

= 1.4.5 =
Bugfixes

= 1.4.3 =
Bugfixes

= 1.4.2 =
Bugfixes, security improvements

= 1.4.1 =
Security fixes. Preparation for new extensions

= 1.4.0 =
Unlocked the learning tree. Modules on every level.

= 1.3.1 =
Prompt for database update if necessary

= 1.3.0 =
Added a new module upload ui

= 1.2.2 =
Fixed a bug that prevented modules from loading

= 1.2.1 =
Improved SCORM 1.2 support, bugfixes

= 1.2.0 =
Added support for SCORM 1.2 modules, bugfixes

= 1.1.1 =
WordPress 5.2 compatibility, styling fixes, minor improvements

= 1.1 =
Added support for php version 5.6 and up, new display modes, bugfixes, etc.

= 1.0 =
This is the initial release. Fixes bugs from the development release.

== Screenshots ==
1. Creating a course structure
2. Handling permissions
