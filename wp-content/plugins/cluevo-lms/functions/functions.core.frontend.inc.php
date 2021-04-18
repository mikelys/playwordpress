<?php

/**
 * Register frontend scripts
 *
 */
function cluevo_scorm_plugin_scripts() {
  wp_enqueue_script('jquery');
  wp_register_script('cluevo-scorm-wrapper', plugins_url('/js/scorm_wrapper.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true);
  wp_register_script('cluevo-scorm-parms', plugins_url('/js/scorm-parms.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true);
  wp_register_script('cluevo-scorm', plugins_url('/js/cluevo.js', plugin_dir_path(__FILE__)), array('cluevo-lightbox'), CLUEVO_VERSION, true);
  wp_register_script('cluevo-lightbox', plugins_url('/js/cluevo-lightbox.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true);
  wp_localize_script('cluevo-scorm', 'cluevoWpApiSettings', array( 'root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest') ));  // scorm stuff is finally enqueued in the iframe template when it's needed
  $strings = array(
      "spinner_text" => __("Loading module, one moment please...", "cluevo"),
      "error_loading_module" => __("The module failed to load.", "cluevo"),
      "error_message_close" => __("Close", "cluevo"),
      "sco_select_title" => __("Please select a unit to start.", "cluevo"),
      "message_title_error" => __("Error", "cluevo"),
      "message_module_already_running" => __("A module has already been started.", "cluevo"),
      "message_title_access_denied" => __("Access Denied", "cluevo"),
      "message_access_denied" => __("You do not have the required permissions to access this item.", "cluevo"),
      "lms_connection_error" => __("Failed to establish a connection to the lms.", "cluevo"),
      "start_over_dialog_header" => __("You have saved progress, do want to start a new attempt or resume the previous attempt?", "cluevo"),
      "start_over_opt_resume" => __("Resume", "cluevo"),
      "start_over_opt_reset" => __("New attempt", "cluevo")
  );
    
  wp_localize_script(
    'cluevo-scorm',
    'cluevoStrings',
    $strings
  );
  wp_register_script('user-js', plugins_url('/js/user.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, false);
  wp_enqueue_script('user-js');
  wp_register_script('cluevo-frontend-js', plugins_url('/js/frontend.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, false);
  wp_localize_script(
    'cluevo-frontend-js',
    'cluevoStrings',
    $strings
  );
  wp_enqueue_script('cluevo-frontend-js');
  wp_register_script(
    "vue-js",
    "https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js",
    "",
    "",
    true
  );
  wp_enqueue_script('vue-js');
  wp_register_script('polygraph-js', plugins_url('/js/polygraph-view.js', plugin_dir_path(__FILE__)), [ "vue-js" ], CLUEVO_VERSION, true);
  wp_localize_script('polygraph-js', 'cluevoWpApiSettings', array( 'root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest') ));  // scorm stuff is finally enqueued in the iframe template when it's needed
  wp_enqueue_script('polygraph-js');
  wp_register_script( 'lodash-js', plugins_url('/js/lodash.min.js', plugin_dir_path(__FILE__)), null, false, false );  // utilities
  wp_enqueue_script('lodash-js');
  wp_add_inline_script( 'lodash-js', 'window.lodash = _.noConflict();', 'after' ); // gutenberg compatibility
}

/**
 * Register/Enqueue frontend styles
 *
 */
function cluevo_enque_theme_files() {
  wp_register_style('cluevo-templates-style', plugins_url('/styles/templates.css', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION);
  wp_enqueue_style('cluevo-templates-style');
  wp_enqueue_style('fontawesome5', 'https://use.fontawesome.com/releases/v5.11.2/css/all.css', array(), null );
}

function cluevo_remove_sidebar_class( $class ) {
  $cur = get_post_type();
  $types = [ CLUEVO_METADATA_POST_TYPE, CLUEVO_METADATA_POST_TYPE_COMPETENCE, CLUEVO_METADATA_POST_TYPE_SCORM_MODULE, CLUEVO_METADATA_POST_TYPE_COMPETENCE_AREA, CLUEVO_PAGE_POST_TYPE ];
  if (in_array($cur, $types)) {
    $class = str_replace("has-sidebar", "", $class);
  }
  return $class;
}

function cluevo_load_frontend_dashicons() {
  wp_enqueue_style( 'dashicons' );
}
?>
