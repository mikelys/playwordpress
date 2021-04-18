<?php

/**
 * Register metadata post type
 *
 */
function cluevo_create_metadata_post_type() {
  register_post_type(
    CLUEVO_METADATA_POST_TYPE_COMPETENCE,
    array(
      'labels' => array(
        'name' => __('CLUEVO Competence Posts', "cluevo"),
        'singular_name' => __('CLUEVO Competence Post', "cluevo")
      ),
      'public' => true,
      'has_archive' => false,
      'hierarchical' => true,
      'show_in_menu' => false, // CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE,
      'show_in_rest' => true,
      'supports' => [ 'title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'comments' ],
      'rewrite' => [ 'slug' => __('cluevo/competence', "cluevo") ]
    )
  );

  register_post_type(
    CLUEVO_METADATA_POST_TYPE_COMPETENCE_AREA,
    array(
      'labels' => array(
        'name' => __('CLUEVO Competence Group Posts', "cluevo"),
        'singular_name' => __('CLUEVO Competence Group Post', "cluevo")
      ),
      'public' => true,
      'has_archive' => false,
      'hierarchical' => true,
      'show_in_menu' => false, // CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE,
      'show_in_rest' => true,
      'supports' => [ 'title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'comments' ],
      'rewrite' => [ 'slug' => __('cluevo/competence-group', "cluevo") ]
    )
  );

  register_post_type(
    CLUEVO_METADATA_POST_TYPE_SCORM_MODULE,
    array(
      'labels' => array(
        'name' => __('CLUEVO SCORM Module Posts', "cluevo"),
        'singular_name' => __('CLUEVO SCORM Module Post', "cluevo")
      ),
      'public' => true,
      'has_archive' => false,
      'hierarchical' => true,
      'show_in_menu' => false, // CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE,
      'show_in_nav_menus' => false,
      'show_in_rest' => true,
      'supports' => [ 'title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'comments' ],
      'rewrite' => [ 'slug' => __('cluevo/scorm', "cluevo") ]
    )
  );

  return register_post_type(
    CLUEVO_METADATA_POST_TYPE,
    array(
      'labels' => array(
        'name' => __('CLUEVO LMS Posts', "cluevo"),
        'singular_name' => __('CLUEVO LMS Post', "cluevo")
      ),
      'public' => true,
      'has_archive' => false,
      'hierarchical' => true,
      'show_in_menu' => false, // CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE,
      //'show_in_menu' => CLUEVO_ADMIN_PAGE_LEARNING_STRUCTURE,
      'show_in_rest' => true,
      'supports' => [ 'title', 'editor', 'revisions', 'excerpt', 'thumbnail', 'comments' ],
      'rewrite' => [ 'slug' => 'cluevo/lms' ]
    )
  );
}

/**
 * Register cluevo shortcode
 *
 * @param mixed $atts
 */
function cluevo_add_shortcode($atts, $content) {
  if (is_array($atts)) {
    if (array_key_exists("item", $atts)) {
      $item = cluevo_get_learning_structure_item($atts["item"], get_current_user_id());
      if ($item !== false) { // display item if it exists
        $displayMode = strtolower(get_option("cluevo-modules-display-mode", "Iframe"));
        if ($displayMode == "popup" || $displayMode == "lightbox") {
          do_action('cluevo_enqueue_module_scripts', 'cluevo_enqueue_module_scripts');
        }
        if (!empty($item->module) && $item->module > 0) {
          $lms = new Cluevo(null, get_current_user_id());
          $lms->items = [ $item ];
          $lms->item = $item;
          $lms->item_count = 1;
          $lms->current_item = 0;
          $lms->current_page = $item;
          $lms->shortcode_content = $content;
          $GLOBALS["cluevo"] = $lms;
          $out = '';
          ob_start();
          if (!empty($content)) {
            cluevo_display_template("cluevo-tree-item-shortcode-link");
          } else {
            if (in_array("tile", $atts)) { 
              cluevo_display_template("part-tree-item");
            } else {
              if (in_array("row", $atts)) {
                echo "<div class=\"cluevo-content-list-style-row\">";
                cluevo_display_template("part-tree-item");
                echo "</div>";
              } else {
                cluevo_display_template("cluevo-tree-item-module-shortcode");
              }
            }
          }
          $out = ob_get_clean();
        } else { // if current item is not a module list children
          $lms = new Cluevo($atts["item"], get_current_user_id());
          $GLOBALS['cluevo'] = $lms;
          $out = '';
          ob_start();
          cluevo_display_template("cluevo-tree-item-bare");
          $out = ob_get_clean();
        }
      } else {
        ob_start();
        cluevo_display_template("cluevo-item-not-found");
        $out = ob_get_clean();
      }
    } else {  // if the shortcode has no item arg display the course index
      $lms = new Cluevo(null, get_current_user_id());
      $GLOBALS['cluevo'] = $lms;
      $out = '';
      ob_start();
      cluevo_display_template('cluevo-shortcode-tree-index');
      $out = ob_get_clean();
    }
  } else {  // if the shortcode has no arguments display the course index
    $lms = new Cluevo(null, get_current_user_id());
    $GLOBALS['cluevo'] = $lms;
    $out = '';
    ob_start();
    cluevo_display_template('cluevo-shortcode-tree-index');
    $out = ob_get_clean();
  }

  $lms->shortcode = true;
  return $out;
}

/**
 * Initializes metadata taxonomy
 *
 */
function cluevo_meta_taxonomy_init() {
  // create a new taxonomy
  $regResult = register_taxonomy(
    CLUEVO_TAXONOMY,
    CLUEVO_METADATA_POST_TYPE,
    array(
      'label' => __('CLUEVO Content', "cluevo"),
      'rewrite' => array( 'slug' => 'cluevo-content' ),
      'show_in_rest' => true,
      'public' => false,
      'hierarchical' => true
    )
  );
  $objRegResult = register_taxonomy_for_object_type('CLUEVO', CLUEVO_METADATA_POST_TYPE);

  return (getType($regResult) === "object" && get_class($regResult) === "WP_Taxonomy" && $objRegResult == true);
}

/**
 * Initialize LMS Class
 *
 */
function cluevo_init_lms_class() {
  global $post;
  //echo "<!-- init lms class -->\n";
  $start = microtime(true);
  if (!empty($GLOBALS["cluevo"])) return;
  $userId = get_current_user_id();
  $itemId = null;
  if (!empty($post)) {
    $itemId = cluevo_get_item_id_from_metadata_id($post->ID);
  }
  $lms = new Cluevo($itemId, $userId);
  $GLOBALS['cluevo'] = $lms;
  $end = microtime(true);
  $time = $end - $start;
  //echo "<!-- init time: $time -->\n";
}
/**
 * Register lms pages post type
 *
 */
function cluevo_create_lms_page_post_type() {
  return register_post_type(
    CLUEVO_PAGE_POST_TYPE,
    array(
      'labels' => array(
        'name' => __('CLUEVO Pages', "cluevo"),
        'singular_name' => __('CLUEVO Page', "cluevo")
      ),
      'public' => true,
      'has_archive' => false,
      'hierarchical' => false,
      'show_in_menu' => '', // don't show in admin menu
      'show_in_nav_menus' => true,
      'supports' => [ 'title', 'thumbnail' ],
      'rewrite' => [ 'slug' => 'cluevo/pages' ]
    )
  );
}


/**
 * Display plugin page templates
 *
 * @param mixed $page_template
 */
function cluevo_page_template($page_template) {
  if (get_post_type() === CLUEVO_PAGE_POST_TYPE) {
    $lms = new Cluevo(null, get_current_user_id());
    $GLOBALS['cluevo'] = $lms;
    $tpl = null;
    $title = sanitize_title(get_the_title());
    foreach (CLUEVO_PAGES as $page) {
      $page = sanitize_title($page);
      if (strtolower($title) === $page) {
        $tpl = $page;
        break;
      }
    }
    if (!empty($page)) {
      if (($tpl = locate_template(array(CLUEVO_THEME_TPL_PATH . '/page-' . $page . '.php'))) != '') {
        $page_template = $tpl;
      } else {
        if (file_exists(cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/page-' . $page . '.php')) {
          $page_template = cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/page-' . $page . '.php';
        }
      }
    }
  } else {
    if (get_post_type() === CLUEVO_METADATA_POST_TYPE) {
      $treeId = get_post_meta(get_the_ID(), CLUEVO_METADATA_KEY, true);
      $type = get_post_meta(get_the_ID(), CLUEVO_METADATA_TYPE, true);
      $parent = wp_get_post_parent_id(get_the_ID());
      if ($type !== 'module' || !empty($parent)) {
        if (($tpl = locate_template(array(CLUEVO_THEME_TPL_PATH . '/content-' . get_post_type() . '.php'))) != '') {
          $page_template = $tpl;
        } else {
          if (file_exists(cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/content-' . get_post_type() . '.php')) {
            $page_template = cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/content-' . get_post_type() . '.php';
          }
        }
      } else {
        $name = get_post_meta(get_the_ID(), CLUEVO_METADATA_NAME, true);
        load_lms_module($name); // TODO: wtf is this?
        if (file_exists(cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/content-' . get_post_type() . '-module.php')) {
          $page_template = cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . 'templates/content-' . get_post_type() . '-module.php';
        }
      }
    }
  }

  return $page_template;
}

function cluevo_display_media_module($args) {
  $module = $args["module"];
  $item = $args["item"];
  $valid = [
    strtolower(__('Video', "cluevo")),
    strtolower(__("Audio", "cluevo"))
  ];

  if (!empty($module)) {
    if (in_array($module->type_name, $valid)) {
      $out = '<video class="cluevo-media-module ' . esc_attr($module->type_name) . '" controls src="' . esc_attr($item->iframe_index) . '" data-module-id="' . esc_attr($module->module_id) . '"></video>';
      $out = apply_filters('cluevo_filter_module_embed', $out);
      echo $out;
    }
  }
}

function cluevo_display_scorm_module($args) {
  $module = $args["module"];
  $item = $args["item"];
  $valid = [
    strtolower(__('SCORM 2004', "cluevo")),
    "scorm-2004",
    "scorm 2004",
    "scorm"
  ];

  if (!empty($module)) {
    if (in_array($module->type_name, $valid)) {
      $dir = cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $module->module_dir . "/";
      $scos = cluevo_find_scos($dir);
      $scoSelect = '<div class="cluevo-scorm-module-iframe-container">';
      $src = esc_attr($item->iframe_index);
      if (count($scos) > 1) {
        $src = '';
        $scoSelect .= '<div class="cluevo-sco-select"><label for="sco-select">' . esc_html__("Please select a unit", "cluevo") . '<select size="1" class="iframe-sco-select">';
        $scoSelect .= '<option value="0">' . esc_html__("Please select a unit", "cluevo") . '</option>';
        foreach ($scos as $key => $sco) {
          $href = cluevo_get_conf_const('CLUEVO_MODULE_URL') . $module->module_dir . "/" . $sco["href"];
          $scoSelect .= '<option value="' . $href . '">' . $sco["title"] . '</option>';
        }
        $scoSelect .= '</select></label></div>';
      }
      $out = $scoSelect . '<iframe id="cluevo-module-iframe" data-module-id="' . esc_attr($module->module_id) . '" data-item-id="' . esc_attr($item->item_id) . '" data-src="' . $src . '"></iframe></div>';
      $out = apply_filters('cluevo_filter_module_embed', $out);
      echo $out;
    }
  }
}

function cluevo_enqueue_module_scripts() {
  wp_enqueue_script('cluevo-scorm-wrapper');
  wp_enqueue_script('cluevo-scorm-parms');
  wp_enqueue_script('cluevo-lightbox');
  wp_enqueue_script('cluevo-scorm');
}

function cluevo_redirect_login_page() {
  global $post;
  if (get_post_type() !== CLUEVO_PAGE_POST_TYPE) return;
  if ($post->post_title !== "Login") return;

  $enabled = get_option("cluevo-login-enabled", "");
  $loginPage = (int)get_option("cluevo-login-page", 0); 

  if (!$enabled) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    get_template_part( 404 ); exit();
  }

  if ($loginPage === 0) return;

  switch ($loginPage) {
    case -1:
      wp_redirect(wp_login_url());
      break;
    default:
      $page = get_permalink($loginPage);
      wp_redirect($page);
      die();
    }
}


?>
