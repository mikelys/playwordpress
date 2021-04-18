<?php
if (!defined("CLUEVO_ACTIVE")) exit;
// Get plugin update data
add_filter('pre_set_site_transient_update_plugins', 'cluevo_modify_plugins_transient', 10, 1);
// Data for 'View details' popup
add_filter('plugins_api', 'cluevo_modify_plugin_details', 10, 3);
// update database after updates
add_action( 'plugins_loaded', 'cluevo_update_db_check' );

function cluevo_update_db_check() {
  global $cluevo_plugin_db_version;
  $curDatabaseVersion = get_option(CLUEVO_DB_VERSION_OPT_KEY);

  if (version_compare($curDatabaseVersion, $cluevo_plugin_db_version) === -1) {
    cluevo_create_database();
  }
}

function cluevo_modify_plugins_transient($args) {
  $slug = basename(__DIR__) . "/" . basename(__FILE__);;

  if (empty($args))
    return;

  if (empty($args->checked))
    return $args;

  $response = wp_remote_post(CLUEVO_UPDATE_URL);
  if (!is_wp_error($response)) {
    if (($response["response"]["code"] == 200) && is_array($response)) {
      if (isset($args->response)) {
        $json = json_decode($response["body"]);
        if (property_exists($json, "slug") && property_exists($json, "new_version")) {
          if (version_compare(ltrim($args->checked[$slug], "v"), ltrim($json->version, "v")) === -1) {
            foreach ($json as $prop => $value) {
              if (is_object($value)) {
                $json->{$prop} = json_decode(json_encode($value), true);
              }
            }
            $args->response[$slug] = $json;
          } else {
            return $args;
          }
        } else {
          $response = new WP_Error("plugins_api_failed", __("The update request returned an invalid response.", "cluevo"));
        }
      } else {
        $response = new WP_Error("plugins_api_failed", __("An error occurred while updating the plugin.", "cluevo"));
      }
    } else {
      $response = new WP_Error("plugins_api_failed", __("The update server returned an error.", "cluevo"));
    }
  }

  return $args;
}

function cluevo_modify_plugin_details($result, $action, $args) {
  $slug = basename(__DIR__) . "/" . basename(__FILE__);;

  if (empty($args))
    return $result;

  if (!isset($args->slug) || ($args->slug != "cluevo"))
		return $result;

  if( $action !== 'plugin_information' )
    return $result;

  $plugins = get_site_transient('update_plugins');
  $curVersion = (!empty($plugins) && property_exists($plugins, "checked")) ? $plugins->checked[$slug] : 0;
  $args->version = $curVersion;

  $response = wp_remote_post(CLUEVO_UPDATE_URL);
  if (!is_wp_error($response)) {
    if (($response["response"]["code"] == 200) && is_array($response)) {
      $json = json_decode($response["body"]);
      if (property_exists($json, "slug") && property_exists($json, "new_version")) {
        if (version_compare(ltrim($curVersion, "v"), ltrim($json->new_version, "v")) === -1) {
          foreach ($json as $prop => $value) {
            if (is_object($value)) {
              $json->{$prop} = json_decode(json_encode($value), true);
            }
          }
          return $json;
        }
      } else {
        $response = new WP_Error("plugins_api_failed", __("The update request returned an invalid response.", "cluevo"));
      }
    } else {
      $response = new WP_Error("plugins_api_failed", __("The update server returned an error.", "cluevo"));
    }
  } else {
    $response = new WP_Error("plugins_api_failed", __("An error occurred while checking for updates.", "cluevo"));
  }

  return $response;
}
?>
