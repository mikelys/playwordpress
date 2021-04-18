<?php
/**
 *
 * @link              https://www.cluevo.at
 * @since             1.0.0
 * @package           cluevo
 *
 * @wordpress-plugin
 * Plugin Name:       CLUEVO LMS
 * Description:       CLUEVO LMS ist ein Plugin das deine WordPress Installation in ein Learning Management System verwandelt
 * Version:           1.5.2
 * Author:            CLUEVO
 * Author URI:        https://www.cluevo.at/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cluevo
 * Domain Path:       /lang

 cluevo is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 cluevo is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with cluevo. If not, see http://www.gnu.org/licenses/gpl-2.0.txt
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once('conf/config.inc.php');  // various config variables
define('CLUEVO_REQUIRED_PHP_VERSION', '5.6');
define('CLUEVO_REQUIRED_MYSQL_VERSION', '5.6');

if (version_compare(phpversion(), CLUEVO_REQUIRED_PHP_VERSION) <= 0) {
  define("PHP_VERSION_OUTDATED", true);
} else {
  define("PHP_VERSION_OUTDATED", false);
}

add_action('init', 'cluevo_load_plugin_textdomain');
function cluevo_load_plugin_textdomain() {
  $moFile = WP_LANG_DIR . '/' . CLUEVO_TEXT_DOMAIN . '/' . CLUEVO_TEXT_DOMAIN . '-' . get_locale() . '.mo';

  load_textdomain(CLUEVO_TEXT_DOMAIN, $moFile);
  $result = load_plugin_textdomain(CLUEVO_TEXT_DOMAIN); //, false, dirname(plugin_basename(__FILE__)) . '/lang/');
  if (!$result) {
    $locale = get_locale();
    if (strtolower(substr($locale, 0, 2)) == 'de') {
      $moFile = plugin_dir_path(__FILE__) . '/lang/cluevo-de_DE.mo';
    } else {
      if (file_exists(plugin_dir_path(__FILE__) . '/lang/cluevo-' . $locale)) {
      } else {
        $moFile = plugin_dir_path(__FILE__) . '/lang/cluevo-en.mo';
      }
    }
    $dir = plugin_dir_path(__FILE__);
    load_textdomain(CLUEVO_TEXT_DOMAIN, $moFile);
  }
}

if (PHP_VERSION_OUTDATED === false) {

  define("CLUEVO_ACTIVE", true);

  require_once('classes/class.cluevo.inc.php');
  require_once('classes/class.item.inc.php');
  require_once('classes/class.user.inc.php');
  require_once('classes/class.group.inc.php');
  require_once('classes/class.competence.inc.php');
  require_once('classes/class.competence_area.inc.php');
  require_once('classes/class.acl.inc.php');
  require_once('classes/class.permission.inc.php');
  require_once('functions/functions.inc.php');
  require_once('functions/functions.core.inc.php');
  require_once('functions/functions.core.admin.inc.php');
  require_once('functions/functions.core.frontend.inc.php');
  require_once('functions/functions.module-management.inc.php');
  require_once('functions/functions.users.inc.php');
  require_once('functions/functions.permissions.inc.php');
  require_once('functions/functions.tree.inc.php');
  require_once('functions/functions.metadata.inc.php');
  require_once('functions/functions.progress.inc.php');
  require_once('functions/functions.competence.inc.php');
  require_once('functions/functions.user-profile.inc.php');
  require_once('functions/functions.utilities.inc.php');
  require_once('install/plugin-activate.inc.php');  // contains functions that are used then the plugin is activated, like db table creation
  require_once('install/plugin-uninstall.inc.php');
  require_once('admin-views/plugin-settings-page-general-settings.php');
  require_once('admin-views/plugin-settings-page-lms.php');
  require_once('admin-views/plugin-settings-page-reports.php');
  require_once('admin-views/plugin-settings-page-competence.php');
  require_once('admin-views/plugin-settings-page-users.php');
  require_once 'rest/rest-api.php';

  //add_action('init', 'cluevo_start_session', 1);
  function cluevo_start_session() {
    if (!session_id()) {
      session_start();
    }
  }

  register_activation_hook(__FILE__, 'cluevo_plugin_install');  // Creates database tables
  register_activation_hook(__FILE__, 'cluevo_flush_rewrite_rules');
  register_activation_hook(__FILE__, 'cluevo_create_lms_pages');  // Creates posts for frontend pages
  register_activation_hook(__FILE__, 'cluevo_create_directories');  // Creates directories needed for module storage
  register_activation_hook(__FILE__, 'cluevo_create_module_archive_htaccess');  // Creates .htaccess to protect module zips from external access
  register_activation_hook(__FILE__, 'cluevo_create_cluevo_uploads_htaccess');  // Creates .htaccess to protect the uploads directory from rogue php files
  register_uninstall_hook(__FILE__, 'cluevo_plugin_uninstall');

  add_shortcode(CLUEVO_SHORTCODE, 'cluevo_add_shortcode');

  add_action("admin_menu", "cluevo_init_menu_items");  // Adds menu entries in admin area
  add_action("admin_init", "cluevo_init_admin_styles");  // Admin styles
  add_action("admin_init", "cluevo_init_admin_scripts");
  add_action("admin_init", "cluevo_init_module_download");  // Module downloads
  add_action('init', 'cluevo_create_metadata_post_type');  // Register metadata post type
  add_action('init', 'cluevo_create_lms_page_post_type');  // Register lms pages posts type
  add_action('init', 'cluevo_meta_taxonomy_init');  // init metadata taxonomy
  add_action('wp_enqueue_scripts', 'cluevo_scorm_plugin_scripts');  // Frontend scorm javascripts
  add_action('template_redirect', 'cluevo_init_lms_class');  // Initializes lms class for the frontend
  add_action('template_redirect', 'cluevo_redirect_login_page');
  add_action('init', 'cluevo_set_user_last_seen');  // Sets the last seen timestamp for a user
  add_action("update_option_lms-tree-new", "cluevo_redirect_on_tree_change", 10, 2);  // Needed for learning structure admin page, redirects to the tree page where the selected tree is loaded

  add_filter('single_template', 'cluevo_page_template');  // Display cluevo templates

  add_action('wp_enqueue_scripts', 'cluevo_enque_theme_files');  // Frontend styles

  // include('tests/functions.hooks.inc.php'); // hook test

  add_action('user_register', 'cluevo_create_lms_user_on_wp_registration');
  add_filter( 'body_class', 'cluevo_remove_sidebar_class', 20 );

  add_action('cluevo_display_module', 'cluevo_display_media_module');
  add_action('cluevo_display_module', 'cluevo_display_scorm_module');
  add_action('cluevo_enqueue_module_scripts', 'cluevo_enqueue_module_scripts');

  add_action('cluevo_render_learning_structure_ui', 'cluevo_render_learning_structure_ui');
  add_action('cluevo_enqueue_lms_structure_js', 'cluevo_enqueue_lms_structure_js');
  add_action('cluevo_render_lms_modules_ui', 'cluevo_render_module_ui');
  add_action('cluevo_enqueue_lms_modules_ui_js', 'cluevo_enqueue_lms_modules_ui_js');
  add_action('cluevo_render_lms_page_tabs', 'cluevo_render_lms_module_ui_tab');
  add_action('cluevo_render_lms_page_tabs', 'cluevo_render_lms_structure_tab');

  add_action('cluevo_init_admin_page', 'cluevo_inject_admin_style_overrides');
  add_action('after_setup_theme', 'cluevo_enable_post_features');

  add_action('in_admin_header', 'cluevo_add_help_tab');
  add_action('load-edit.php', 'cluevo_add_help_tab');
  add_action('load-post.php', 'cluevo_add_help_tab');

  add_action( 'upgrader_process_complete', 'cluevo_plugin_updated', 10, 2 );
  add_action( 'wp_enqueue_scripts', 'cluevo_load_frontend_dashicons' );
  add_action( 'in_plugin_update_message-cluevo-lms/cluevo-lms.php', 'cluevo_add_plugin_update_message', 10, 2 );

  //add_filter( 'plugin_row_meta', 'cluevo_add_plugin_row_meta', 10, 4 );
  add_action( 'after_plugin_row_cluevo-lms/cluevo-lms.php', 'cluevo_add_after_plugin_row', 10, 3);

  add_filter( 'extra_plugin_headers', 'add_cluevo_plugin_headers' );
  //add_action('admin_notices', 'cluevo_mysql_compat_notice');

  if (is_admin()) {
    if (!empty($_GET["cluevo-update-db"])) {
      global $cluevo_plugin_db_version;
      $curDatabaseVersion = get_option(CLUEVO_DB_VERSION_OPT_KEY);
      if ($curDatabaseVersion != $cluevo_plugin_db_version) {
        cluevo_create_database();
        update_option( 'cluevo-display-db-update-result', true);
      }
    }
  }

} else {
  add_action('admin_notices', 'cluevo_php_compat_notice');
}
