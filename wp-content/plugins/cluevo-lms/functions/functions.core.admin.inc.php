<?php
if (!defined("CLUEVO_ACTIVE")) exit;

/**
 * Initialize submenu pages
 *
 */
function cluevo_init_menu_items() {
  add_menu_page(
    __("CLUEVO", "cluevo"),
    __("CLUEVO", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_LMS,
    "cluevo_render_lms_page",
    "dashicons-welcome-learn-more"
  );

  add_submenu_page(
    CLUEVO_ADMIN_PAGE_LMS,
    __("Learning Management", "cluevo"),
    __("Learning Management", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_LMS,
    "cluevo_render_lms_page"
  );

  add_submenu_page(
    CLUEVO_ADMIN_PAGE_LMS,
    __("User Management", "cluevo"),
    __("User Management", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_USER_MANAGEMENT,
    "cluevo_render_user_management_page"
  );

  add_submenu_page(
    CLUEVO_ADMIN_PAGE_LMS,
    __("Reporting", "cluevo"),
    __("Reporting", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_REPORTS,
    "cluevo_render_reports_page"
  );

  add_submenu_page(
    CLUEVO_ADMIN_PAGE_LMS,
    __("Competence", "cluevo"),
    __("Competence", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_COMPETENCE,
    "cluevo_render_competence_areas_page"
  );

  add_submenu_page(
    CLUEVO_ADMIN_PAGE_LMS,
    __("Settings", "cluevo"),
    __("Settings", "cluevo"),
    "manage_options",
    CLUEVO_ADMIN_PAGE_GENERAL_SETTINGS,
    "CluevoSettingsPage::render"
  );
}

/**
 * Register admin styles
 *
 * Also removes emojis from admin pages
 *
 */
function cluevo_init_admin_styles() {
  wp_register_style('lms-admin-css', plugins_url('/styles/admin.css', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION);  // admin page styles
  wp_enqueue_style('lms-admin-css');
}

/**
 * Used to let admins download modules
 *
 */
function cluevo_init_module_download() {
  if (!current_user_can("Administrator")) return;

  $page = (!empty($_GET["page"])) ? cluevo_strip_non_alphanum_dash($_GET["page"]) : null;
  $tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : null;
  if (!empty($page) && !empty($tab)) {
    if ($page === CLUEVO_ADMIN_PAGE_LMS && $tab === CLUEVO_ADMIN_TAB_LMS_MODULES) {
      $dl = (!empty($_GET["dl"]) && is_numeric($_GET["dl"])) ? (int)$_GET["dl"] : null;
      if (!empty($dl) && is_numeric($dl)) {
        $module = cluevo_get_module($dl);
        if (!empty($module->module_zip)) {
          $file = cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . $module->module_zip;
          if (file_exists($file)) {
            $mime = wp_check_filetype($file);

            header('Content-Type: ' . $mime["type"]); // always send this
            header('Content-Length: ' . filesize($file));
            header('Content-Disposition: attachment; filename="' . $module->module_zip . '"');
            $last_modified = gmdate('D, d M Y H:i:s', filemtime($file));
            $etag = '"' . md5($last_modified) . '"';
            header("Last-Modified: $last_modified GMT");
            header('ETag: ' . $etag);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 100000000) . ' GMT');
            readfile($file);
            exit;
          } else {
            header("HTTP/1.0 404 Not Found");
            echo "File not found.\n";
            die();
          }
        } else {
          header("HTTP/1.0 404 Not Found");
          echo "File not found.\n";
          die();
        }
      }
    }
  }
}

function cluevo_create_lms_user_on_wp_registration($intUserId) {
  $auto = get_option("cluevo-auto-add-new-users", "on");
  if ($auto === "on") {
    $result = cluevo_make_lms_user($intUserId);
    if ($result) {
      cluevo_add_users_to_group($intUserId, CLUEVO_DEFAULT_GROUP_USER);
    }
  }
}

function cluevo_inject_admin_style_overrides() {
  wp_register_style('admin-overrides-css', plugins_url('/styles/admin-overrides.css', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION);
  wp_enqueue_style('admin-overrides-css');
}

function cluevo_enable_post_features() {
  $posts = [
    CLUEVO_METADATA_POST_TYPE_COMPETENCE,
    CLUEVO_METADATA_POST_TYPE_COMPETENCE_AREA,
    CLUEVO_METADATA_POST_TYPE_SCORM_MODULE,
    CLUEVO_METADATA_POST_TYPE
  ];
  add_theme_support( 'post-thumbnails', $posts );
}

function cluevo_add_help_tab() {
  $screen = get_current_screen();

  if ( $screen->post_type != CLUEVO_METADATA_POST_TYPE && strpos($screen->base, 'cluevo') === false )
    return;

  $args = [
    'id'      => 'cluevo-help',
    'title'   => __("CLUEVO Posts / Pages", "cluevo"),
    'callback' => 'cluevo_display_help_tab_posts'
  ];

  $screen->add_help_tab( $args );
  $screen->add_help_tab( [
    'id' => 'cluevo-lms',
    'title' => __("LMS", "cluevo"),
    'callback' => 'cluevo_display_help_tab_lms'
  ]);
  $screen->add_help_tab( [
    'id' => 'cluevo-competence',
    'title' => __("Competence", "cluevo"),
    'callback' => 'cluevo_display_help_tab_competence'
  ]);
  $screen->add_help_tab( [
    'id' => 'cluevo-reports',
    'title' => __("Reporting", "cluevo"),
    'callback' => 'cluevo_display_help_tab_reports'
  ]);
  $screen->add_help_tab( [
    'id' => 'cluevo-permissions',
    'title' => __("Users / Permissions", "cluevo"),
    'callback' => 'cluevo_display_help_tab_permissions'
  ]);
  $screen->add_help_tab( [
    'id' => 'cluevo-settings',
    'title' => __("Settings", "cluevo"),
    'callback' => 'cluevo_display_help_tab_settings'
  ]);
}

function cluevo_plugin_updated($upgrade, $opt) {
  $name = plugin_basename( __FILE__ );
  if( $opt['action'] == 'update' && $opt['type'] == 'plugin' && isset( $opt['plugins'] ) ) {
    foreach ($opt['plugins'] as $plugin) {
      if ($plugin == $name) {
        cluevo_plugin_install();
        break;
      }
    }
  }
}

function cluevo_add_plugin_update_message( $data, $response ) {
  $plugins = cluevo_get_extensions();
  $notices = [];
  $curVersion = get_file_data(__FILE__, array('Version'))[0];
  $newVersion = $data["new_version"];
  foreach ( $plugins as $file => $plugin ) {
    $least = (!empty($plugin["CLUEVO requires at least"])) ? $plugin["CLUEVO requires at least"] : __("Unknown", "cluevo");
    $tested = (!empty($plugin["CLUEVO tested up to"])) ? $plugin["CLUEVO tested up to"] : __("Unknown", "cluevo");
    $notices[$plugin["Name"]] = [ "tested" => false, "compatible" => false, "tested_version" => $tested, "compatible_version" => $least ];
    if ( !empty($least) ) {
      $result = version_compare($newVersion, $least);
      switch ($result) {
      case -1:
        $notices[$plugin["Name"]]["compatible"] = false;
        break;
      case 0:
        $notices[$plugin["Name"]]["compatible"] = true;
        break;
      case 1:
        $notices[$plugin["Name"]]["compatible"] = false;
        break;
      }
    }
    if ( !empty($tested) ) {
      $result = version_compare($newVersion, $tested);
      switch ($result) {
      case -1:
        $notices[$plugin["Name"]]["tested"] = false;
        break;
      case 0:
        $notices[$plugin["Name"]]["tested"] = true;
        break;
      case 1:
        $notices[$plugin["Name"]]["tested"] = false;
        break;
      }
    }
  }
  if (!empty($notices)) {
    $out =  "<div class=\"cluevo-plugin-update-info\">";
    $out .=  "<div class=\"cluevo-update-compat-text\">" . esc_html__("You have one or more CLUEVO extensions installed. Please check their compatibility before updating.", "cluevo") . "</div>";
    $out .=  "<table class=\"cluevo-ext-update-info\" cellspacing=\"0\">";
    $out .=  "<tr><th>" . __("Plugin", "cluevo") . "</th><th>" . __("tested", "cluevo") . "</th><th>" . __("compatible", "cluevo") . "</th></tr>";
    foreach ($notices as $plugin => $msg) {
      $comp = ($msg["compatible"] === true) ? "yes" : "no";
      $test = ($msg["tested"] === true) ? "yes" : "no";
      $out .=  "<tr><td>$plugin</td><td><span class=\"dashicons dashicons-" . esc_attr($test) . "\"></span>(" . $msg["tested_version"] . ")</td><td><span class=\"dashicons dashicons-" . esc_attr($comp) . "\"></span></td></tr>";
    }
    $out .=  "</table>\n";
    $out .=  "</div>";
    echo "</p>" . wp_kses_post($out) . "<p class=\"dummy\">";
  }
}

function cluevo_add_after_plugin_row($file, $data, $status) {
  $plugins = cluevo_get_extensions();
  $notices = [];
  $curVersion = $data["Version"];

  foreach ( $plugins as $file => $plugin ) {
    $least = (!empty($plugin["CLUEVO requires at least"])) ? $plugin["CLUEVO requires at least"] : __("Unknown", "cluevo");
    $tested = (!empty($plugin["CLUEVO tested up to"])) ? $plugin["CLUEVO tested up to"] : __("Unknown", "cluevo");
    $notices[$plugin["Name"]] = [ "tested" => false, "compatible" => false, "tested_version" => $tested, "compatible_version" => $least ];
    if ( !empty($least) ) {
      $result = version_compare($curVersion, $least);
      switch ($result) {
      case -1:
        $notices[$plugin["Name"]]["compatible"] = false;
        break;
      case 0:
        $notices[$plugin["Name"]]["compatible"] = true;
        break;
      case 1:
        $notices[$plugin["Name"]]["compatible"] = false;
        break;
      }
    }
    if ( !empty($tested) ) {
      $result = version_compare($curVersion, $tested);
      switch ($result) {
      case -1:
        $notices[$plugin["Name"]]["tested"] = false;
        break;
      case 0:
        $notices[$plugin["Name"]]["tested"] = true;
        break;
      case 1:
        $notices[$plugin["Name"]]["tested"] = false;
        break;
      }
    }
  }
  if (!empty($notices)) {
    $display = false;
    $statusClass = "active";
    if ($data["new_version"] && version_compare($curVersion, $data["new_version"]) != 0) {
      $statusClass .= " update";
    }
    $out = '<tr class="' . $statusClass . '"><th class="check-column"></th><td colspan="2"><div class="cluevo-plugin-update-info notice inline notice-warning notice-alt">';
    $out .=  "<div class=\"cluevo-update-compat-text\">" . esc_html__("One or more of your CLUEVO extensions are either not compatible or have not been tested with the currently installed CLUEVO LMS version. Please deactivate or update these extensions as soon as possible to avoid errors.", "cluevo") . "</div>";
    $out .=  "<table class=\"cluevo-ext-update-info\" cellspacing=\"0\">";
    $out .=  "<tr><th>" . __("Plugin", "cluevo") . "</th><th>" . __("tested", "cluevo") . "</th><th>" . __("compatible", "cluevo") . "</th></tr>";
    foreach ($notices as $plugin => $msg) {
      if (!$msg["compatible"] || !$msg["tested"]) {
        $display = true;
        $comp = ($msg["compatible"] === true) ? "yes" : "no";
        $test = ($msg["tested"] === true) ? "yes" : "no";
        $out .=  "<tr><td>$plugin</td><td><span class=\"dashicons dashicons-" . esc_attr($test) . "\"></span>(" . $msg["tested_version"] . ")</td><td><span class=\"dashicons dashicons-" . esc_attr($comp) . "\"></span></td></tr>";
      }
    }
    $out .=  "</table>\n";
    $out .= '</div></td></tr>';
    if ($display) echo $out;
  }
}

function add_cluevo_plugin_headers( $headers ) {
  if ( !in_array( 'CLUEVO requires at least', $headers ) )
    $headers[] = 'CLUEVO requires at least';

  if ( !in_array( 'CLUEVO tested up to', $headers ) )
    $headers[] = 'CLUEVO tested up to';

  return $headers;
}

//function cluevo_mysql_compat_notice() {
  //$mysqlVersion = cluevo_get_mysql_server_version();
  //if (stripos($mysqlVersion, "maria") !== false || $mysqlVersion === false) {
    //return;
  //}
  //if (version_compare($mysqlVersion, CLUEVO_REQUIRED_MYSQL_VERSION) == -1) {
    //echo '<div class="notice notice-warning">
      //<p>' . sprintf(__("CLUEVO LMS requires a MySQL server version of at least %s. You are currently running version %s. Please update your MySQL server version. There will be issues if you run this plugin with an outdated MySQL server.", "cluevo"), CLUEVO_REQUIRED_MYSQL_VERSION, $mysqlVersion) . '</p>
      //</div>';
  //}
//}

/**
 * Redirect to the new tree when the option is changed
 *
 * @param mixed $old
 * @param mixed $new
 */
function cluevo_redirect_on_tree_change($old, $new) {
  wp_redirect("admin.php?page=" . CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE . "&tree_id=$new");
  exit;
}

function cluevo_init_admin_scripts() {
  wp_register_script('cluevo-admin-common', plugins_url('/js/admin-common.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true);
  wp_enqueue_script('cluevo-admin-common');
}

function cluevo_php_compat_notice() {
  echo '<div class="notice notice-error">
    <p>' . sprintf(__("CLUEVO LMS requires at least PHP version %s. Your PHP version: %s. Please update your PHP version to use this plugin.", "cluevo"), CLUEVO_REQUIRED_PHP_VERSION, phpversion()) . '</p>
    </div>';
}

$GLOBALS["cluevo_groups"] = [];
function cluevo_init_group_cache() {
  $GLOBALS["cluevo_groups"] = cluevo_get_user_groups();
}
add_action("init", "cluevo_init_group_cache");

//function cluevo_add_plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
//if ( strpos( $plugin_file_name, basename(__FILE__) ) ) {

//// You can still use `array_unshift()` to add links at the beginning.
//$links_array[] = '<a href="#">FAQ</a>';
//$links_array[] = '<a href="#">Support</a>';
//}

//return $links_array;
//}

?>
