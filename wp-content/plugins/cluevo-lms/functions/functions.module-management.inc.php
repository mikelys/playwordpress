<?php
if (!defined("CLUEVO_ACTIVE")) exit;
/**
 * Handles the module upload
 *
 * Unpacks files, creates db entries and metadata posts
 */
function cluevo_handle_module_upload(&$errors, &$messages, &$handled, &$result = null) {
  if(!empty($_FILES["module-file"]["name"])) {
    $fileType = $_FILES['module-file']['type'];
    $file = $_FILES['module-file']['tmp_name'];
    $filename = strtolower(pathinfo(sanitize_file_name($_FILES["module-file"]['name']),  PATHINFO_FILENAME));
    $ext = strtolower(pathinfo(sanitize_file_name($_FILES["module-file"]['name']),  PATHINFO_EXTENSION));

    $blacklistExt = cluevo_get_blacklisted_extensions();
    $blacklistNames = cluevo_get_blacklisted_filenames();
    if (in_array($ext, $blacklistExt) || in_array($filename, $blacklistNames)) {
      $errors[] = __("This type of file is not allowed", "cluevo");
      return;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    $type = sanitize_file_name(strtolower(cluevo_get_module_type_name_from_mime_type($mime)));

    if (!file_exists(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH'))) {
      mkdir(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH'), 0755, true);
    }

    $targetDirExists = true;
    if (!file_exists(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . "$type/")) {
      $targetDirExists = @mkdir(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . "$type/", 0755, true);
    }
    $archivePath = cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . strtolower($type . '/' . sanitize_file_name($_FILES["module-file"]['name']));
    $zipFile = strtolower(sanitize_file_name($_FILES["module-file"]['name']));
    $realPath = cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $filename . '/';
    $overwrite = false;

    $lang = (!empty($_POST["language"]) && ctype_alpha($_POST["language"])) ? sanitize_text_field($_POST["language"]) : null;
    $parentModule = null;
    $parentModuleId = null;
    $tmp_pid = (!empty($_POST["parent-module-id"]) && is_numeric($_POST["parent-module-id"])) ? (int)$_POST["parent-module-id"] : null;
    if (!empty($tmp_pid)) {
      $parentModule = cluevo_get_module($tmp_pid);
      $parentModuleId = $parentModule->module_id;
    }

    if (move_uploaded_file($file, $archivePath)) {
      $handled = false;
      do_action('cluevo_activate_module', [
        "module" => $archivePath,
        "mime" => $mime,
        "messages" => &$messages,
        "errors" => &$errors,
        "parentModuleId" => $parentModuleId,
        "lang" => $lang,
        "handled" => &$handled,
        "result" => &$result
      ]);
      if (!$handled) {
        $errors[] = __("No handler for this content type could be found.", "cluevo");
        unlink($archivePath);
      }
    }
  }
}

function cluevo_url_exists($strUrl) {
  $headers = @get_headers($strUrl);
  foreach ($headers as $h) {
    if(!$h || strpos($h, "200")) {
      return true;
    }
  }

  return false;
}

function cluevo_handle_module_download($strUrl, &$errors, &$messages, &$handled, &$result = null) {
  if (empty($strUrl)) {
    $errors[] = __("URL should not be empty", "cluevo");
    return;
  }
  if (!cluevo_url_exists($strUrl)) {
    $errors[] = __("File does not exist", "cluevo");
    return;
  }

  $url = esc_url_raw($strUrl, ['http', 'https', 'ftp']);
  $path = parse_url(urldecode($url), PHP_URL_PATH);
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  $validExtensions = [ "zip", "mp3", "wav", "mp4", "webm" ];
  $handled = false;
  if (in_array($ext, $validExtensions)) {
    $title = sanitize_text_field(pathinfo($path, PATHINFO_FILENAME));
    $filename = strtolower(pathinfo(sanitize_file_name(trim(basename($path), '/')),  PATHINFO_FILENAME)) . ".$ext";
    $tmpPath = cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . strtolower('tmp/' . $filename);
    file_put_contents($tmpPath, fopen($url, 'r'));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpPath);
    $type = sanitize_file_name(strtolower(cluevo_get_module_type_name_from_mime_type($mime)));
    $targetDirExists = true;
    if (!file_exists(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . "$type/")) {
      $targetDirExists = @mkdir(cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . "$type/", 0755, true);
    }
    $archivePath = cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . strtolower("$type/" . $filename);
    if ($targetDirExists && @rename($tmpPath, $archivePath)) {
      $result = [];
      $module = null;
      do_action('cluevo_activate_module', [
        "module" => $archivePath,
        "title" => $title,
        "mime" => $mime,
        "messages" => &$messages,
        "errors" => &$errors,
        "parentModuleId" => null,
        "lang" => null,
        "handled" => &$handled,
        "result" => &$result
      ]);
      if ($handled) {
        return $result;
      }
    } else {
      $errors[] = __("An error occurred while moving the file to the target directory.", "cluevo");
    }
  } else {
    $result = [];
    do_action('cluevo_handle_module_url_install', [
      'url' => $url,
      "messages" => &$messages,
      "errors" => &$errors,
      "parentModuleId" => null,
      "lang" => null,
      "handled" => &$handled,
      "result" => &$result,
      "module" => &$module
    ]);
    if ($handled) {
      return $result;
    }
  }
  if (!$handled) {
    $errors[] = __("The module failed to install.", "cluevo");
  }
}

/**
 * Recursively creates metadata posts of a given item
 *
 * Recursively creates parent's posts if necessary
 *
 * @param mixed $item
 * @param mixed $intParentId
 * @param mixed $tree
 */
function cluevo_create_metadata_post($item, $intParentId, &$tree) {
  $parentPostId = $intParentId;

  if (!empty($item->parent_id)) { // if the item has a parent we need to have the parent post first before we can create the item's post

    if ($item->new) {
      if (array_key_exists($item->parent_id, $tree)) {
        $parentItem = $tree[$item->parent_id];
      } else {
        $parentItem = cluevo_get_learning_structure_item($item->tree_id);
      }
    } else {
      $parentItem = cluevo_get_learning_structure_item($item->parent_id);
    }

    $parentPostId = $parentItem->metadata_id;
    $post = get_post($parentPostId);
    if (empty($post) || empty($parentPostId)) { // check if post exists or metadata id is empty
      if (!empty($post)) {
        $parentPostId = $post->ID;
      }
      if (!array_key_exists($item->parent_id, $tree)) {
        $tree[$item->parent_id] = cluevo_get_learning_structure_item($item->parent_id);
      }
      $parentPostId = cluevo_create_metadata_post($tree[$item->parent_id], $parentPostId, $tree);
    }
  }

  if (get_post($item->metadata_id) === null || empty($item->metadata_id)) {
    $id = cluevo_create_metadata_page($item, $parentPostId);
    $tree[$item->item_id]->metadata_id = $id;
  } else {
    cluevo_update_metadata_page($item, $parentPostId);
    $id = $item->metadata_id;

  }
  return $id;
}

/**
 * Returns a textbox and dropdown containing the selected modules as options
 *
 * @param mixed $name
 * @param mixed $modules
 * @param mixed $intSelected
 */
function cluevo_render_module_list($name, $modules, $intSelected = null, $boolHideButton = false) {
  $out = "<input type=\"text\" data-target=\"name\" class=\"module-name sortable-name\" value=\"$name\" /> ";
  $labelVisible = 'hidden';
  $buttonVisible = 'hidden';
  $id = '';
  $name = '';
  if (!empty($intSelected)) {
    $selected = null;
    foreach ($modules as $module) {
      if ($module->module_id == $intSelected) {
        $selected = $module;
        break;
      }
    }
    $labelVisible = '';
    if (!empty($selected)) {
      $id = $selected->module_id;
      $name = $selected->module_name;
    }
  } else {
    $buttonVisible = '';
  }
  $buttonVisible = ($boolHideButton) ? 'hidden' : $buttonVisible;
  $out .= "<div class=\"module-name-label $labelVisible\" data-value=\"$id\" data-target=\"module-id\">
    <div class=\"dashicons dashicons-welcome-learn-more label-icon\"></div>
    <div class=\"content\">$name</div>
    <div class=\"dashicons dashicons-edit cluevo-edit-module label-icon-right\"></div>
    <div class=\"dashicons dashicons-dismiss remove-module label-icon-right\"></div>
  </div>";
  $out .= "<div class=\"cluevo-btn cluevo-make-module $buttonVisible\">" . __("Insert Module", "cluevo") . "</div>";
  return $out;
}

/**
 * Callback to handle creation of new course groups
 *
 * Creates the database entries and metadata pages for tree items
 *
 * @param mixed $name
 */
function cluevo_handle_create_learning_structure($name) {
  $treeMetadataId = wp_insert_post( [ "post_title" => $name, "post_status" => "publish", 'post_type' => CLUEVO_METADATA_POST_TYPE ] );
  $terms = get_terms([ 'taxonomy' => CLUEVO_TAXONOMY, 'hide_empty' => false ]);
  if (is_array($terms)) {
    foreach($terms as $term) {
      if ($term->name == __("Course Group", "cluevo")) {
        wp_set_post_terms($treeMetadataId, [$term->term_id], CLUEVO_TAXONOMY);
        break;
      }
    }
  }
  $treeIndex = cluevo_create_learning_structure($name, $treeMetadataId);
  update_option("cluevo-selected-course-group", $treeIndex);
  return $treeIndex;
}

/**
 * Handles saving of course groups
 *
 * @param mixed $tree
 */
function cluevo_save_learning_structure($tree) {
  $treeId = (isset($_POST["lms-tree-id"])) ? (int)$_POST["lms-tree-id"] : null;
  $treeName = (isset($_POST["lms-tree-name"])) ? sanitize_text_field($_POST["lms-tree-name"]) : __("New", "cluevo");

  $curItems = cluevo_get_learning_structure_items($treeId);

  $tree = json_decode(urldecode($tree));

  $array = json_decode(json_encode($tree), true); // create an assoc array from the json object

  $page = cluevo_get_metadata_page($treeId); // get tree metadata page, and create/update
  $treeMetadataId = 0;
  if (!empty($page)) {
    $treeMetadataId = $page->ID;
    if ($page->post_title != $treeName) {
      wp_update_post( [ "ID" => $treeMetadataId, "post_title" => $treeName ]);
      cluevo_update_learning_structure($treeId, $treeName, $treeMetadataId); // update tree item with metadata id
    }
  } else {
    $treeMetadataId = wp_insert_post( [ "post_title" => $treeName, "post_status" => "publish", 'post_type' => CLUEVO_METADATA_POST_TYPE ] );
    $terms = get_terms([ 'taxonomy' => CLUEVO_TAXONOMY, 'hide_empty' => false ]);
    if (is_array($terms)) {
      foreach($terms as $term) {
        if ($term->name == __("Course Group", "cluevo")) {
          wp_set_post_terms($treeMetadataId, [$term->term_id], CLUEVO_TAXONOMY);
          break;
        }
      }
    }
    cluevo_update_learning_structure($treeId, $treeName, $treeMetadataId); // update tree item with metadata id
  }

  //$idMap = []; // the tree uses temp. ids for new items, we map the tmp ids to the real db ids
  foreach ($array as $key => $item) {
    $item["item_id"] = $item["id"]; // items come without item_id prop from the ui
    $item["parent_id"] = (empty($item["parent_id"])) ? $treeId : $item["parent_id"];
    //$idMap[$item["item_id"]] = $item["item_id"];
    $array[$key] = CluevoItem::from_array($item);
  }

  foreach ($array as $key => $item) { // create/update posts for tree items
    $item = CluevoItem::from_array($item);
    $metadataId = $item->metadata_id;
    if (empty($metadataId)) { // if the metadata id is empty we need to create a new post
      $metadataId = cluevo_create_metadata_post($item, $treeMetadataId, $array);
    } else {
      wp_update_post( ['ID' => $metadataId, 'post_title' => $item->name ]);
    }
  }

  foreach ($array as $key => $item) {
    $item = CluevoItem::from_array($item);
    if ($treeId !== null) $item->tree_id = $treeId;  // set the tree id for each element

    $item->parent_id = (empty($item->parent_id)) ? $treeId : $item->parent_id; // if the parent is empty it is a child of the tree

    if ($item->level == 1) // if the item level is 1 (course) the parent id is the tree item
      $item->parent_id = $treeId;

    $tree->{$key}->metadata_id = $item->metadata_id;
    $itemId = $item->item_id;
    if (!empty($item->new)) { // if the item is new we create a db entry, otherwise we update the existing entry
      $itemId = cluevo_create_learning_structure_item($item);
      $item->item_id = $itemId; // we got a new id and replace the tmp id with this one and update the map
    } else {
      cluevo_update_learning_structure_item($item);
    }
  }

  foreach ($array as $newItem) {
    $newItem = CluevoItem::from_array($newItem);
    foreach ($curItems as $key => $item) {
      if ($item->item_id == $newItem->item_id) {
        unset($curItems[$key]);
      }
    }
  }

  foreach ($curItems as $item) {
    cluevo_remove_learning_structure_item($item->item_id);
  }

  cluevo_cleanup_metadata_posts(json_decode(json_encode($tree), true));

  return json_encode($tree);
}

/**
 * Creates the metadata post for a module
 *
 * Returns the new post id on success
 *
 * @param string $strFilename
 *
 * @return int|WP_Error
 */
function cluevo_create_module_metadata_post($strFilename) {
  $id = wp_insert_post(
    [
      "post_title" => $strFilename,
      "post_status" => "publish",
      "post_type" => CLUEVO_METADATA_POST_TYPE_SCORM_MODULE
    ]
  );
  $terms = get_terms([ 'taxonomy' => 'CLUEVO', 'hide_empty' => false ]);
  if (is_array($terms)) {
    foreach($terms as $term) {
      if ($term->name == __("SCORM Module", "cluevo")) {
        wp_set_post_terms($id, [$term->term_id], __("SCORM MODULE", "cluevo"));
        break;
      }
    }
  }

  return $id;
}

/**
 * Renders an item recursively
 *
 * @param mixed $item
 * @param array $modules
 * @param mixed $forceId
 */
function cluevo_render_courses($item, $modules = array(), $forceId = null) {

  // these variables contain learn unit information. TODO: develop a system of storing and retrieving this information to make it easily extensible. Maybe create an interface or include php files
  $url = remove_query_arg(['create-metadata-page']);
  if (get_class($item) === 'CluevoItem') $item->load_settings();
  $name = (!empty($item->name)) ? $item->name : '';
  $level = (!empty($item->level)) ? $item->level : 0;
  $pointsNeeded = (!empty($item->points_required)) ? $item->points_required : 0;
  $pointsWorth = (!empty($item->points_worth)) ? $item->points_worth: 0;
  $levelRequired = (!empty($item->level_required)) ? $item->level_required: 0;
  $dependencies = (!empty($item->dependencies)) ? $item->dependencies : [ 'modules' => [ 'normal' => [], 'blocked' => [], 'inherited' => [] ], 'other' => [ 'normal' => [], 'blocked' => [], 'inherited' => [] ] ];
  $repeatInterval = (!empty($item->repeat_interval)) ? $item->repeat_interval : 0;
  $repeatIntervalType = (!empty($item->repeat_interval_type)) ? $item->repeat_interval_type : 'day';
  $moduleId = (!empty($item->module_id)) ?'data-module-id="' . $item->module_id . '"' : '';
  $displayMode = ($item->type == "module") ?'data-display-mode="' . $item->display_mode . '"' : '';
  $defaultDisplayMode = strtolower(get_option('cluevo-modules-display-mode'));
  $isLink = (trim($item->get_setting("item-is-link")) !== "") ? true : false;
  //$moduleId = 0;
  $metadataId = 0;
  if (!empty($item)) {
    $item->id = (empty($item->id)) ? 0 : $item->id;
    $id = 'item-' . $item->id;
    $id = (!empty($forceId)) ? $forceId : $id;
    $metadataId = $item->metadata_id;
  } else {
    $id = (!empty($forceId)) ? $forceId : 0;
  }

  $hasDependencies = false;
  foreach ($dependencies as $depType => $deps) {
    foreach ($deps as $dep) {
      if (!empty($dep)) {
        $hasDependencies = true;
        break;
      }
    }
    if ($hasDependencies) break;
  }


  $classes = [];
  $classes[] = ($item->published) ? "published" : "draft";
  if (!empty($item->module_id) && (int)$item->module_id > 0) $classes[] = "module-assigned";
  if ($hasDependencies) $classes[] = "has-dependencies";
  if ($isLink) $classes[] = "is-link";
  $classes = implode(" ", $classes);

  echo '<li id="' . $id . '"
    class="lms-tree-item ' . $classes . '"
    data-item-id="' . $item->item_id . '"
    data-name="' . $item->name . '"
    data-id="' . $item->id . '"
    data-module-id="' . $item->module_id . '"
    data-level="' . $item->level . '"
    data-type="' . CLUEVO_LEARNING_STRUCTURE_LEVELS[$item->level] . '"
    data-dependencies=\'' . json_encode($item->dependencies) . '\'
    data-repeat-interval="' . $item->repeat_interval . '"
    data-repeat-interval-type="' . $item->repeat_interval_type  . '"
    data-metadata-id="' . $item->metadata_id . '"
    data-published="' . $item->published . '"
    data-item-is-link="' . esc_attr($isLink) . '"
    data-login-required="' . $item->login_required . '"' . $displayMode . '>';
?>
<div class="handle">
  <div class="drag-handle"></div>
  <span class="title">
    <?php echo cluevo_render_module_list($name, $modules, (!empty($item->module) && $item->module > 0) ? $item->module : null, !empty($item->children)); ?>
    <span class="type fade">
    <?php
      $nextItemType = 'element';
      switch ($item->type) {
      case "course":
        echo __("Course", "cluevo");
        $nextItemType = __("Chapter", "cluevo");
        break;
      case "chapter":
        echo __("Chapter", "cluevo");
        $nextItemType = __("Module", "cluevo");
        break;
      case "module":
        echo __("Module", "cluevo");
        break;
      default:
        echo "";
      }
    ?>
    </span>
    <span class="tree-item-id fade shortcode copy-shortcode" title="<?php echo __("Copy Shortcode", "cluevo"); ?>">[<?php echo (!empty($item)) ? $item->id : ""; ?>]</span>
  </span>
  <div class="buttons">
    <span class="cluevo-item-is-link dashicons dashicons-admin-links"></span>
    <img class="has-dependencies-icon" alt="<?php esc_attr_e("This item has dependencies", "cluevo"); ?>" title="<?php esc_attr_e("This item has dependencies", "cluevo"); ?>" src="<?php echo plugins_url("/images/icon-dependency-neg.svg", plugin_dir_path(__FILE__)); ?>" />
    <div class="publish cluevo-btn cluevo-btn-square cluevo-button-primary" title="<?php ($item->published) ? esc_attr_e("Published", "cluevo") : esc_attr_e('Draft', "cluevo"); ?>">
    <?php if($item->published) { ?><span class="dashicons dashicons-visibility"></span>
    <?php } else { ?><span class="dashicons dashicons-hidden"></span><?php } ?>
    </div>
    <?php if ($item->level < 3) { ?>
    <div class="add cluevo-btn cluevo-btn-square cluevo-button-primary" <?php echo (!empty($item->module_id) && $item->module_id > 0) ? 'disabled="disabled"' : ''; ?> title="<?php esc_attr_e(sprintf(__("Add %s to this item", "cluevo"), $nextItemType)); ?>"><img class="plugin-logo" src="<?php echo plugins_url("/images/icon-add-child.svg", plugin_dir_path(__FILE__)); ?>" /></div>
    <?php } ?>
    <?php if (get_page($metadataId) !== null) { ?>
      <a class="metadata cluevo-btn cluevo-btn-square metadata-edit-link" title="<?php _e("Open this elements post", "cluevo"); ?>" href="<?php echo get_edit_post_link($metadataId); ?>" target="_blank"><span class="dashicons dashicons-wordpress"></span></a>
    <?php } else { ?>
      <a class="cluevo-btn cluevo-btn-square cluevo-btn-primary" title="<?php esc_attr_e("Create item page", "cluevo"); ?>" href="<?php echo add_query_arg('create-metadata-page', $item->id, $url); ?>"><span class="dashicons dashicons-wordpress"></span></a>
    <?php } ?>
    <div class="shortcode cluevo-btn cluevo-btn-square copy-shortcode" title="<?php _e("Copy Shortcode", "cluevo"); ?>">[s]</div>
    <div class="meta-toggle cluevo-btn cluevo-btn-square" title="<?php _e("Element Settings", "cluevo"); ?>"><span class="dashicons dashicons-admin-generic"></span></div>
    <div class="remove cluevo-btn cluevo-btn-square" title="<?php _e("Delete Element", "cluevo"); ?>"><span class="dashicons dashicons-trash"></span></div>
    <div class="expand cluevo-btn cluevo-btn-square <?php if ($item->level > 2) echo "disabled"; ?>"><span class="dashicons dashicons-arrow-down"></span></div>
  </div>
</div>

<div class="meta">

  <div class="meta-content-container">
    <h2><?php echo __("Settings", "cluevo"); ?></h2>

    <?php if ($item->type == "module" || (!empty($item->module_id) && (int)$item->module_id > 0)) { ?>
    <?php $tmpMode = (!empty($item->display_mode)) ? $item->display_mode : ""; ?>
    <?php $iframePosition = $item->get_setting('iframe-position'); ?>
    <div class="meta-container display-mode">
      <div class="label"><?php _e("Display Mode", "cluevo"); ?></div>
      <p class="help">
        <?php _e("Determines how modules are displayed.", "cluevo"); ?>
      </p>
        <div class="display-mode-container display-mode input-container">
          <label><?php _e("Mode", "cluevo"); ?></label>
          <select data-target="display-mode" class="setting">
            <option value="">Standard</option>
            <option value="iframe" <?php if ($tmpMode == "iframe") echo "selected"; ?>>Iframe</option>
            <option value="popup" <?php if ($tmpMode == "popup") echo "selected"; ?>>Popup</option>
            <option value="lightbox" <?php if ($tmpMode == "lightbox") echo "selected"; ?>>Lightbox</option>
          </select>
      </div>
      <div class="iframe-position input-container <?php if ($tmpMode === "iframe" || $defaultDisplayMode === 'iframe') echo "visible"; ?> <?php if ($defaultDisplayMode === "iframe") echo "forced"; ?>">
          <label><?php _e("Position", "cluevo"); ?></label>
          <select data-target="iframe-position" class="setting">
          <option value="start" <?php if ($iframePosition == "start") echo "selected"; ?>><?php esc_html_e('Top of the page', "cluevo"); ?></option>
          <option value="end" <?php if ($iframePosition == "end") echo "selected"; ?>><?php esc_html_e('Bottom of the page', 'cluevo'); ?></option>
          </select>
        </div>
    </div>
    <?php } ?>

    <?php do_action('cluevo_tree_item_settings', $item); ?>

    <div class="meta-container dependency-container">
    <div class="label"><?php _e("Requirements", "cluevo"); ?></div>
      <p class="help">
        <?php _e("Defines the requirements users have to fulfill to access this element.", "cluevo"); ?>
      </p>
      <div class="dep-checkbox-container" data-target="dependencies">
      </div>
    </div>

    <div class="meta-container points global">
      <div class="label"><?php _e("Points", "cluevo"); ?></div>

      <p class="help">
        <?php _e("Defines the worth in points of an item or respectively how many points a user has to have to gain access.", "cluevo"); ?>
      </p>

      <div class="point-wrap input-list-container inline">
        <div class="points-container points-worth input-container">
          <label><?php _e("Worth", "cluevo"); ?></label>
          <input type="number" min="0" value="<?php echo $pointsWorth; ?>" data-target="points-worth"/>
        </div>

        <div class="points-container practice-points input-container">
          <label><?php _e("Practice points", "cluevo"); ?></label>
          <input type="number" min="0" value="<?php echo $item->practice_points; ?>" data-target="practice-points"/>
        </div>

        <div class="points-container points-required input-container">
          <label><?php _e("Required", "cluevo"); ?></label>
          <input type="number" min="0" value="<?php echo $pointsNeeded; ?>" data-target="points-required"/>
        </div>
      </div>
    </div>

    <div class="meta-container level global">
      <div class="label"><?php _e("Required level", "cluevo"); ?></div>
      <p class="help">
        <?php _e("Defines the level a user has to have reached to access an item.", "cluevo"); ?>
      </p>
      <div class="input-list-container inline">
        <div class="input-container">
        <label><?php echo __("Level", "cluevo"); ?></label>
          <input type="number" min="0" value="<?php echo $levelRequired; ?>" data-target="level-required"/>
        </div>
      </div>
    </div>

<?php if ($level == count(CLUEVO_LEARNING_STRUCTURE_LEVELS) - 1) { ?>
  <!-- <div class="meta-container repeating global">
  <div class="label"><?php _e("Module must be repeated periodically", "cluevo"); ?></div>
    <p class="help">
      <?php _e("Defines the interval in which a module has to be repeated.", "cluevo"); ?>
    </p>
    <div class="meta-input-fields-container">
      <input type="number" min="0" value="<?php echo $repeatInterval; ?>" data-target="repeat-interval"/>
      <select class="repeat-interval-type" data-target="repeat-interval-type">
        <?php foreach (CLUEVO_REPEAT_INTERVAL_TYPES as $key => $value) { ?>
        <option value="<?php echo $key; ?>"<?php if ($repeatIntervalType === $key) echo ' selected="selected"'; ?>><?php echo $value; ?></option>
        <?php } ?>
      </select>
    </div>
  </div> -->
<?php } ?>

  </div>
</div>

<?php
  // render children of the current item
  $nextLevel = $level + 1;
  echo "<ol id=\"level-$nextLevel\" data-level=\"$nextLevel\">\n";
  if (!empty($item->children)) {
    foreach($item->children as $key => $child) {
      cluevo_render_courses($child, $modules);
    }
  }
  echo "</ol>\n";
  echo "\t</li>\n";
}

/**
 * Outputs the module ui and handles deletion of modules
 *
 */
function cluevo_render_module_ui($errors = [], $messages = []) {
  $tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_LMS_STRUCTURE;

  $plugin_dir = plugin_dir_path(__DIR__);
  $scorm_dir = $plugin_dir . "scorm-modules";
  $archive_dir = $plugin_dir . "scorm-modules-archive/";

  if (file_exists($scorm_dir) || file_exists($archive_dir)) {
    $messages[] = __("There are modules inside the old module Directory. Click here to start the migration: ", "cluevo") . " <a href=\"" . add_query_arg("migrate", "true") . "\">[" . __("migrate modules", "cluevo") . "]</a>";
  }

  // handle module deletion
  $deleted = false;
  $del_module = (!empty($_GET["delete-module"]) && is_numeric($_GET["delete-module"])) ? (int)$_GET["delete-module"] : null;
  if (!empty($del_module)) {
    // check if modules exists in database, delete module and archive zip and remove from database
    $modules = cluevo_get_modules();
    $moduleId = $del_module;
    $module = cluevo_get_module($moduleId);
    if (!empty($module)) {
      $delModule = $module->module_dir;
      $delPath = cluevo_get_conf_const('CLUEVO_ABS_MODULE_PATH') . $delModule;
      $delZip = cluevo_get_conf_const('CLUEVO_ABS_MODULE_ARCHIVE_PATH') . $module->module_zip;
      if (!empty($module->module_dir) && file_exists($delPath) && !empty($delPath)) {
        if (!empty($delModule)) {
          cluevo_delete_directory($delPath);
        }
        if (!empty($module->module_zip) && file_exists($delZip) && !empty($delZip))
          unlink($delZip);
        cluevo_remove_metadata_page($moduleId);
        cluevo_remove_module($moduleId);
        $deleted = true;
      } else {
        cluevo_remove_metadata_page($moduleId);
        cluevo_remove_module($moduleId);
        $deleted = true;
      }
    }
  }

  $action = (!empty($_POST["action"])) ? sanitize_text_field($_POST["action"]) : null;
  if (!empty($action) && $action === "update-module-language") {
    $module_id = (int)$_POST["module_id"];
    $old_lang = sanitize_text_field($_POST["cur_module_lang"]);
    $new_lang = sanitize_text_field($_POST["new_module_lang"]);
    if ($module_id !== 0) {
      $result = cluevo_set_module_language($module_id, $old_lang, $new_lang);
      if ($result === true) {
        $messages[] = __("Language changed.", "cluevo");
      } else {
        $errors[] = __("Failed to change language. The module probably already exists in this language.", "cluevo");
      }
    } else {
      $errors[] = __("Invalid module", "cluevo");
    }
  }

  // create metadata page
  $pageCreated = false;
  $create_page = (!empty($_GET["create-metadata-page"]) && is_numeric($_GET["create-metadata-page"])) ? (int)$_GET["create-metadata-page"] : null;
  if (!empty($create_page)) {
    $moduleId = $create_page;
    if (!cluevo_get_module_metadata_page($moduleId)) { // create metadata page for the uploaded module if the page doesn't yet exist
      $module = cluevo_get_module($moduleId);
      if (!empty($module)) {
        $id = wp_insert_post(
          [
            "post_title" => $module->module_name,
            "post_status" => "publish",
            "post_type" => CLUEVO_METADATA_POST_TYPE_SCORM_MODULE
          ]
        );
        $terms = get_terms([ 'taxonomy' => CLUEVO_TAXONOMY, 'hide_empty' => false ]);
        if (is_array($terms)) {
          foreach($terms as $term) {
            if ($term->name == __("SCORM Module", "cluevo")) {
              wp_set_post_terms($id, [$term->term_id], __('SCORM Module', "cluevo"));
              break;
            }
          }
        }
        cluevo_update_module_metadata_id($moduleId, $id);
        $pageCreated = true;
      }
    }
  }

  $url = remove_query_arg(['create-metadata-page', 'delete-module']);
  $modules = cluevo_get_modules();
  $page = CLUEVO_ADMIN_PAGE_LMS;

  if (isset($_GET["add-demo-modules"])) {
    cluevo_install_demos();
    cluevo_js_redirect(admin_url("admin.php?page=$page&tab=$tab"));
  }

  $moduleTypes = [];
  do_action('cluevo_register_module_types', [ "types" => &$moduleTypes ]);

?>
<?php if (!empty($moduleTypes) && is_array($moduleTypes)) { ?>
<div class="cluevo-add-module-overlay" data-max-upload-size="<?php echo esc_attr(wp_max_upload_size()); ?>">
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container">
        <div class="modal-header">
          <h3><?php esc_html_e('Add Module', 'cluevo'); ?></h3>
          <button class="close"><span class="dashicons dashicons-no-alt"></span></button>
        </div>
        <div class="modal-body">
          <div class="cluevo-add-module-content-container">
              <div class="module-type-selection">
                <div class="add-module-text"><?php esc_html_e('You can add new modules to your LMS here.', 'cluevo'); ?></div>
                <h2><?php esc_html_e('The following module types are currently available', 'cluevo'); ?></h2>
                <div class="module-list">
                  <?php foreach ($moduleTypes as $key => $type) { ?>
                  <div class="module-type <?php if (!empty($type["alt-icon-class"])) { echo $type["alt-icon-class"]; } ?>" data-module-index="<?php echo esc_attr($key); ?>">
                    <?php if (!empty($type['icon'])) { ?><div class="module-icon"><img src="<?php echo esc_attr($type['icon']); ?>" /></div><?php } ?>
                    <?php if (!empty($type["name"])) { ?>
                    <div class="module-type-name"><?php echo esc_html($type['name']); ?></div>
                    <?php } ?>
                  </div>
                  <?php } ?>
                </div>
              </div>
              <div class="upload-progress">
                <h2 class="progress-text"><?php esc_html_e("The module is being uploaded, one moment please...", "cluevo"); ?></h2>
                <div class="cluevo-progress-container">
                  <span
                    class="cluevo-progress"
                    data-value=""
                    data-max="100"
                  ></span>
                </div>
                <div class="result-container"></div>
                <div class="cluevo-btn continue"><?php esc_html_e("Continue", "cluevo"); ?></div>
              </div>
              <div class="module-description-container">
                <div class="module-type hint">
                  <p><?php esc_html_e('Select a module type to display further information.', 'cluevo'); ?></p>
                </div>
                <?php foreach ($moduleTypes as $key => $type) { ?>
                <div class="module-type <?php if (!empty($type["alt-icon-class"])) { echo $type["alt-icon-class"]; } ?>" data-module-index="<?php echo esc_attr($key); ?>">
                  <div class="description-container">
                    <?php if (!empty($type['icon'])) { ?><div class="module-icon"><img src="<?php echo esc_attr($type['icon']); ?>" /></div><?php } ?>
                    <div>
                      <h3><?php echo esc_html($type['name']); ?></h3>
                      <p><?php echo $type['description']; ?></p>
                    </div>
                  </div>
                  <?php if (!empty($type["field"])) { ?>
                  <form method="post" enctype="multipart/form-data" class="cluevo-module-form <?php if (!empty($type['form-class'])) echo esc_attr($type["form-class"]); ?>" action="<?php echo esc_url(admin_url("admin.php?page=$page&tab=$tab"), ['http', 'https']); ?>" data-type="<?php echo esc_attr($type["name"]); ?>">
                    <?php if (!empty($type["field"]) && $type["field"] == "text") { ?>
                    <input type="text" name="module-dl-url" placeholder="<?php echo esc_attr($type['field-placeholder']); ?>" />
                    <?php } ?>
                    <?php if (!empty($type["field"]) && $type["field"] == "file") { ?>
                    <input type="file" name="module-file" placeholder="<?php echo esc_attr($type['field-placeholder']); ?>" />
                    <?php } ?>
                    <?php if (!empty($type["field"]) && $type["field"] == "mixed") { ?>
                    <div class="input-switch">
                      <input type="text" name="module-dl-url" placeholder="https://" />
                      <label class="cluevo cluevo-module-install-type-file">
                        <div class="cluevo-btn"><?php echo esc_html($type["button-label"]); ?></div>
                        <input type="file" name="module-file" value="" <?php if (!empty($type['filter'])) echo 'accept="' . $type['filter'] . '"'; ?> />
                      </label>
                    </div>
                    <?php } ?>
                    <?php if (!empty($type["field"]) && $type["field"] == "textarea") { ?>
                    <textarea name="module-dl-url" placeholder="<?php echo esc_attr($type['field-placeholder']); ?>"></textarea>
                    <?php } ?>
                    <input type="submit" class="cluevo-btn auto cluevo-btn-primary disabled" value="<?php echo __("Install Module", "cluevo"); ?>" disabled/>
                  </form>
                  <?php if (!empty($type["field"]) && ($type["field"] == "file" || $type["field"] == 'mixed')) { ?>
                  <p><?php _e("Max. Filesize: ", "cluevo") . esc_html_e(cluevo_human_filesize(wp_max_upload_size())); ?></p>
                  <div class="cluevo-notice cluevo-notice-error cluevo-filesize hidden"><p><?php esc_html_e(sprintf(__("The file you are trying to upload is too big. The maximum upload size is %s", "cluevo"), cluevo_human_filesize(wp_max_upload_size()))); ?></div>
                  <?php } ?>
                <?php } elseif (!empty($type["alt-content"])) { ?>
                  <div class="cluevo-add-module-alt-content">
                    <?php echo $type["alt-content"]; ?>
                  </div>
                <?php } ?>
                </div>
                <?php } ?>
                <div class="cluevo-btn select-type"><?php esc_html_e('Select another module type', 'cluevo'); ?></div>
              </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>
<?php if ($deleted) cluevo_display_notice("Hinweis", __("Module deleted.", "cluevo")); ?>
<form method="post" enctype="multipart/form-data" id="add-module-form" class="cluevo-module-form" action="<?php echo esc_url(admin_url("admin.php?page=$page&tab=$tab"), ['http', 'https']); ?>">
  <div class="cluevo-btn cluevo-btn-primary add-module"><?php _e('Add Module', 'cluevo'); ?></div>
  <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>" />
  <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>" />
</form>
<?php if (!empty($modules)) { ?>
  <h2><?php esc_html_e("Modules", "cluevo"); ?></h2>
  <?php if ($pageCreated) echo "<p>" . esc_html_e("Module metadata post created.", "cluevo") . "</p>"; ?>
  <div id="module-lang-overlay" class="module-lang-overlay" data-module-id="">
    <div class="module-lang-select-container">
      <div class="module-lang-select" id="module-lang-select">
        <h3><?php echo __("Select Language", "cluevo"); ?></h3>
        <ul>
    <?php
      $langs = cluevo_get_languages();
      foreach ($langs as $lang) {
        echo "<li><label><input type=\"radio\" name=\"module-lang\" value=\"$lang->lang_code\" class=\"module-lang-radio\"> $lang->lang_name</label></li>";
      }
    ?>
      </ul>
    </div>
  </div>
</div>
<?php
  if (!empty($errors)) {
    foreach ($errors as $err) {
      cluevo_display_notice(__("Error", "cluevo"), $err, 'error');
    }
  }
  if (!empty($messages)) {
    foreach ($messages as $msg) {
      cluevo_display_notice(__("Notice", "cluevo"), $msg);
    }
  }
?>
  <table class="cluevo-scorm-modules cluevo-admin-table">
    <tr>
    <th class="module-id">#</th>
    <th class="module-name left"><?php esc_html_e("Module", "cluevo"); ?></th>
    <!-- <th class="module-lang left"><?php esc_html_e("Language", "cluevo"); ?></th> -->
    <th class="module-type left"><?php esc_html_e("Type", "cluevo"); ?></th>
    <th class="module-tools"><?php esc_html_e("Tools", "cluevo"); ?></th>
    </tr>
    <?php
      $tmpId = null;
      $border = false;
?>
    <?php foreach ($modules as $key => $m) { ?>
<?php
      if ($tmpId !== $m->module_id && $tmpId !== null) {
        $border = true;
      }
      $tmpId = $m->module_id;
      $border = false;
?>
    <?php $link = get_edit_post_link($m->metadata_id, "module"); ?>
      <tr <?php if ($border) echo 'class="bordered" '; ?> data-module-id="<?php echo esc_attr($m->module_id); ?>">
      <td><?php echo $m->module_id; ?></td>
      <td class="title left column-title has-row-actions column-primary" data-id="<?php echo esc_attr($m->module_id); ?>"><?php echo $m->module_name; ?></td>
      <td class="type left "><?php echo (!empty($m->type_name)) ? esc_html(apply_filters('cluevo_output_module_type', $m)) : esc_html__('Unknown', "cluevo"); ?></td>
    <td class="module-tools">
      <div class="module-tools-container">
        <a class="cluevo-btn edit-module-name cluevo-btn-square" title="<?php esc_attr_e("Change Name", "cluevo"); ?>" href="#" data-id="<?php echo esc_attr($m->module_id); ?>"><span class="dashicons dashicons-edit"></span></a>
        <?php if (!empty($link)) { ?>
        <a class="cluevo-btn cluevo-btn-square" title="<?php esc_attr_e("Open module page for editing", "cluevo"); ?>" href="<?php echo $link; ?>" target="_blank"><span class="dashicons dashicons-wordpress"></span></a>
      <?php } else { ?>
        <a class="cluevo-btn cluevo-btn-square" title="<?php esc_attr_e("Create module page", "cluevo"); ?>" href="<?php echo add_query_arg('create-metadata-page', $m->module_id, $url); ?>"><span class="dashicons dashicons-wordpress"></span></a>
    <?php } ?>
        <a class="cluevo-btn del-module cluevo-btn-square" title="<?php esc_attr_e("Delete Module", "cluevo"); ?>" href="<?php echo add_query_arg('delete-module', $m->module_id, $url); ?>"><span class="dashicons dashicons-trash"></span></a>
        <?php if (strpos($m->type_name, 'scorm') === false) { ?>
          <p class="cluevo-btn disabled cluevo-btn-square" title="<?php esc_attr_e("No SCORM parameters available for this module", "cluevo"); ?>"><span class="dashicons dashicons-admin-settings"></span></p>
        <?php } else { ?>
          <a class="cluevo-btn cluevo-btn-square" title="<?php esc_attr_e("Browse SCORM parameters", "cluevo"); ?>" href="<?php echo admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS . "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_SCORM_PARMS . "&module=" . $m->module_id); ?>"><span class="dashicons dashicons-admin-settings"></span></a>
        <?php } ?>
        <a class="cluevo-btn cluevo-btn-square" title="<?php esc_attr_e("Browse Reports", "cluevo"); ?>" href="<?php echo admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_REPORTS . "&tab=" . CLUEVO_ADMIN_TAB_REPORTS_PROGRESS . "&module=" . $m->module_id); ?>"><span class="dashicons dashicons-chart-area"></span></a>
        <a class="cluevo-btn cluevo-btn-square <?php echo (empty($m->module_zip)) ? "disabled" : ""; ?>" title="<?php esc_attr_e("Download Module", "cluevo"); ?>" href="<?php if (!empty($m->module_zip)) { echo admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_LMS . "&tab=" . CLUEVO_ADMIN_TAB_LMS_MODULES . "&dl=" . $m->module_id); } else { echo "#"; } ?>"><span class="dashicons dashicons-download"></span></a>
      </div>
    </td>
  </tr>
  <?php $border = false; ?>
  <?php } ?>
</table>
<p class="cluevo-add-demos""><a href="<?php esc_html_e(admin_url("admin.php?page=$page&tab=$tab&add-demo-modules")); ?>"><?php esc_html_e("Add demo course and modules", "cluevo"); ?></a></p>
</div>
<?php 
  } else {
    cluevo_display_notice_html(
      __("Notice", "cluevo"),
      __("No modules have been added yet. Click here to add our demo course and demo modules: ", "cluevo") . "<a href=\"" . esc_html(admin_url("admin.php?page=$page&tab=$tab&add-demo-modules")) . "\">" . __("Add demo course and modules", "cluevo") . "</a>",
      "info"
    );
  }
}

function cluevo_render_lms_structure_tab($tab) {
  $tabClass = ($tab == CLUEVO_ADMIN_TAB_LMS_STRUCTURE) ? 'nav-tab-active' : '';
  echo '<a href="' . admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_LMS . "&tab=" . CLUEVO_ADMIN_TAB_LMS_STRUCTURE) . "\" class=\"nav-tab $tabClass\">" . esc_html__("Learning tree", "cluevo") . "</a>";
}

function cluevo_render_lms_module_ui_tab($tab) {
  $tabClass = ($tab == CLUEVO_ADMIN_TAB_LMS_MODULES) ? 'nav-tab-active' : '';
  echo "<a href=\"" . admin_url("admin.php?page=" . CLUEVO_ADMIN_PAGE_LMS . "&tab=" . CLUEVO_ADMIN_TAB_LMS_MODULES) . "\" class=\"nav-tab $tabClass\">" .  esc_html__("Modules", "cluevo") . "</a>";
}

function cluevo_render_lms_page() {
  $active_tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_LMS_STRUCTURE;
  do_action('cluevo_init_admin_page');
?>
<div class="cluevo-admin-page-container">
  <div class="cluevo-admin-page-title-container">
    <h1><?php esc_html_e("Learning Management", "cluevo"); ?></h1>
    <img class="plugin-logo" src="<?php echo plugins_url("/assets/logo-white.png", plugin_dir_path(__FILE__)); ?>" />
  </div>
  <div class="cluevo-admin-page-content-container">
  <h2 class="nav-tab-wrapper cluevo"><?php do_action('cluevo_render_lms_page_tabs', $active_tab); ?></h2>
<?php 
  switch ($active_tab) {
  case CLUEVO_ADMIN_TAB_LMS_STRUCTURE:
    do_action('cluevo_enqueue_lms_structure_js');
    do_action('cluevo_render_learning_structure_ui');
    break;
  case CLUEVO_ADMIN_TAB_LMS_MODULES:
    $errors = [];
    $messages = [];
    $handled = false;

    if (!empty($_FILES) && !empty($_FILES["module-file"]) && $_FILES["module-file"]["error"] === UPLOAD_ERR_OK) {
      if (!empty($_FILES["module-file"])) {
        cluevo_handle_module_upload($errors, $messages);
      }
    } else {
      if (!empty($_POST["module-dl-url"])) {
        if (filter_var($_POST["module-dl-url"], FILTER_VALIDATE_URL)) {
          $url = esc_url_raw($_POST["module-dl-url"], ['http', 'https', 'ftp']);
          if (!empty($url)) {
            cluevo_handle_module_download($_POST["module-dl-url"], $errors, $messages);
          }
        } else {
          do_action('cluevo_handle_misc_module_url_input', [
            "input" => $_POST["module-dl-url"],
            "handled" => &$handled,
            "result" => &$result,
            "errors" => &$errors,
            "messages" => &$messages
          ]);
          if (!$handled) {
            $messages[] = __("No handler for this content type could be found", "cluevo");
          }
          if (!empty($_POST["ajax"])) {
            die("ok");
          }
        }
      }
    }
    if (!empty($errors)) {
      foreach ($errors as $err) {
        cluevo_display_notice(__("Error", "cluevo"), $err, 'error', true);
      }
    }
    if (!empty($messages)) {
      foreach ($messages as $msg) {
        cluevo_display_notice(__("Notice", "cluevo"), $msg);
      }
    }
    do_action('cluevo_enqueue_lms_modules_ui_js');
    do_action('cluevo_render_lms_modules_ui');
    break;
  default:
    do_action('cluevo_enqueue_lms_structure_js');
    do_action('cluevo_render_learning_structure_ui');
    break;
  }
?>
</div>
</div>
<?php
}

function cluevo_enqueue_lms_structure_js() {

  // development version
  //wp_register_script(
  //"vue-js",
  //"https://cdn.jsdelivr.net/npm/vue/dist/vue.js",
  //"",
  //"",
  //true
  //);

  // production version
  wp_register_script(
    "vue-js",
    "https://cdn.jsdelivr.net/npm/vue",
    "",
    "",
    true
  );

  wp_enqueue_script('vue-js');

  wp_register_script( 'nested-sortable-js', plugins_url('/js/jquery.mjs.nestedSortable.js', plugin_dir_path(__FILE__)), array('jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-selectmenu'), CLUEVO_VERSION, false );  // provides drag'n'drop tree
  wp_register_script( 'lodash-js', plugins_url('/js/lodash.min.js', plugin_dir_path(__FILE__)), null, false, false );  // utilities
  wp_register_script(
    'scorm-plugin-js',
    plugins_url('/js/module-nav.js', plugin_dir_path(__FILE__)),
    array('nested-sortable-js', 'lodash-js', 'vue-js'),
    CLUEVO_VERSION,
    false
  );  // tree management
  wp_add_inline_script( 'lodash-js', 'window.lodash = _.noConflict();', 'after' ); // gutenberg compatibility
  wp_localize_script( 'scorm-plugin-js', 'cluevoWpApiSettings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );  // needed for ajax requests
  wp_enqueue_script( 'nested-sortable-js' );
  wp_enqueue_script( 'scorm-plugin-js' );

  wp_localize_script( 'scorm-plugin-js',
    'strings',
    array(
      'new_course' => __('New Course', "cluevo"),
      'new_chapter' => __('New Chapter', "cluevo"),
      'new_module' => __('New Module', "cluevo"),
      'without_module' => __('Without Module', "cluevo"),
      'delete_item' => __('Really delete this element?', "cluevo"),
      'shortcode_copied' => __('Shortcode copied!', "cluevo"),
      'msg_install_demos' => __("Install demo course and modules? The modules will be downloaded from our homepage and installed to your LMS.", "cluevo"),
      'published' => __("Published", "cluevo"),
      'draft' => __("Draft", "cluevo")
    )
  );

  wp_register_script(
    'cluevo-module-selector',
    plugins_url('/js/module-selector.admin.js', plugin_dir_path(__FILE__)),
    ['scorm-plugin-js'],
    CLUEVO_VERSION,
    true
  );
  wp_localize_script( 'cluevo-module-selector',
    'cluevoApiSettings',
    array(
      'root' => esc_url_raw( rest_url() ),
      'nonce' => wp_create_nonce( 'wp_rest' )
    )
  );
  wp_localize_script( 'cluevo-module-selector',
    'lang_strings', array (
      "insert_module" => __("Insert Module", "cluevo"),
      "label_search" => __("Search", "cluevo"),
      "placeholder_modulename" => __("Search term", "cluevo"),
      "module_search_result_count" => __("Modules", "cluevo"),
      "filter_tile_all" => __("All", "cluevo")
    )
  );
  wp_enqueue_script('cluevo-module-selector');
}

function cluevo_enqueue_lms_modules_ui_js() {
  wp_register_script( 'cluevo-admin-module-page', plugins_url('/js/module-admin-page.js', plugin_dir_path(__FILE__)), array(), CLUEVO_VERSION, true );
  wp_enqueue_script( "cluevo-admin-module-page" );
  wp_localize_script( 'cluevo-admin-module-page',
    'moduleApiSettings',
    array(
      'root' => esc_url_raw( rest_url() ),
      'nonce' => wp_create_nonce( 'wp_rest' )
    )
  );
  wp_localize_script( 'cluevo-admin-module-page',
    'strings', array (
      'confirm_module_delete' => __("Really delete this module? This action can't be undone.", "cluevo"),
      'toggle_install_type_file' => __('Install module from URL', "cluevo"),
      'toggle_install_type_url' => __('Upload module file', "cluevo"),
      'msg_install_demos' => __("Install demo course and modules. The modules will be downloaded from our homepage and installed to your LMS", "cluevo"),
      'rename_module_prompt' => __("New Name", "cluevo"),
      'rename_module_error' => __("The module could not be renamed. A module with the same name may already exist or the module is no longer available.", "cluevo"),
      "upload_success" => __("The module has been uploaded. One moment please while it is being installed.", "cluevo"),
      "module_upload_finished" => __("Installation completed.", "cluevo"),
      "refresh_to_enable" => __("Module uploaded. Refresh the page to enable the tools.", "cluevo"),
      "module_upload_failed" => __("Installation failed.", "cluevo"),
      "upload_error" => __("Upload failed.", "cluevo")
    )
  );
  wp_localize_script( 'cluevo-admin-module-page', 'cluevoWpApiSettings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );  // needed for ajax requests
}

function cluevo_update_module_name($intModuleId, $strNewName) {
  global $wpdb;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_MODULES;
  $result = $wpdb->query(
    $wpdb->prepare("UPDATE $table SET module_name = %s WHERE module_id = %d", [ $strNewName, $intModuleId ])
  );
  return ($result !== false);
}

function cluevo_output_module_type($module) {
  if (!empty($module)) {
    if (!empty($module->scorm_version)) {
      return "SCORM " . $module->scorm_version;
    }
  }

  return ucwords($module->type_name);
}

function cluevo_add_tree_item_close_button_setting($item) {
  if ($item->type === "module" || (!empty($item->module_id) && (int)$item->module_id > 0)) {
?>
<div class="meta-container hide-lightbox-close-button">
  <div class="label"><?php _e("Lightbox Settings", "cluevo"); ?></div>
  <p class="help">
    <?php _e("These settings configure the lightbox.", "cluevo"); ?>
  </p>
  <div class="label sub">Schließen Button</div>
    <table class="cluevo-meta-settings-group">
      <tr>
      <td>
        <label><?php _e("Hide Button", "cluevo"); ?></label>
      </td>
      <td>
        <input type="checkbox" value="1" class="setting" data-target="hide-lightbox-close-button" <?php echo $item->get_setting('hide-lightbox-close-button') ? 'checked' : ''; ?>/>
      </td>
      </tr>
      <tr>
        <td>
          <label><?php _e("Button Text", "cluevo"); ?></label>
        </td>
        <td>
          <input placeholder="&times;" type="text" value="<?php echo esc_attr($item->get_setting('lightbox-close-button-text')); ?>" class="setting" data-target="lightbox-close-button-text" />
        </td>
      </tr>
      <tr>
        <td>
          <label><?php _e("Button Position", "cluevo"); ?></label>
        </td>
        <td>
          <select size="1" name="lightbox-close-button-position" class="setting" data-target="lightbox-close-button-position">
            <option value=""><?php esc_html_e("Default", "cluevo"); ?></option>
            <option value="top-left" <?php if ($item->get_setting('lightbox-close-button-position') == "top-left") echo "selected"; ?>><?php esc_html_e("top left", "cluevo"); ?></option>
            <option value="top-center" <?php if ($item->get_setting('lightbox-close-button-position') == "top-center") echo "selected"; ?>><?php esc_html_e("top center", "cluevo"); ?></option>
            <option value="top-right" <?php if ($item->get_setting('lightbox-close-button-position') == "top-right") echo "selected"; ?>><?php esc_html_e("top right", "cluevo"); ?></option>
            <option value="bottom-left" <?php if ($item->get_setting('lightbox-close-button-position') == "bottom-left") echo "selected"; ?>><?php esc_html_e("bottom left", "cluevo"); ?></option>
            <option value="bottom-center" <?php if ($item->get_setting('lightbox-close-button-position') == "bottom-center") echo "selected"; ?>><?php esc_html_e("bottom center", "cluevo"); ?></option>
            <option value="bottom-right" <?php if ($item->get_setting('lightbox-close-button-position') == "bottom-right") echo "selected"; ?>><?php esc_html_e("bottom right", "cluevo"); ?></option>
          </select>
        </td>
      </tr>
    </table>
  </div>
<?php }
}

function cluevo_add_tree_empty_setting($item) {
?>
<div class="meta-container info-box-settings">
  <div class="label"><?php _e("Info Box Settings", "cluevo"); ?></div>
  <p class="help">
    <?php _e("Customize the message that are displayed if an element is empty or permissions are required.", "cluevo"); ?>
  </p>
    <table class="cluevo-meta-settings-group">
    <?php if ($item->type != "module") { ?>
      <tr>
        <td><?php esc_html_e("Hide Info Box", "cluevo"); ?></td>
        <td>
          <input type="checkbox" name="hide-info-box" <?php if ($item->get_setting("hide-info-box")) echo esc_attr("checked"); ?> data-target="hide-info-box" class="setting" />
        </td>
      </tr>
      <tr>
        <td><?php esc_html_e("Element is empty text", "cluevo"); ?></td>
        <td>
          <input type="text" name="element-is-empty-text" value="<?php echo esc_attr($item->get_setting("element-is-empty-text")); ?>" data-target="element-is-empty-text" class="setting" />
        </td>
      </tr>
    <?php } ?>
      <tr>
        <td><?php esc_html_e("Hide access denied box", "cluevo"); ?></td>
        <td>
          <input type="checkbox" name="hide-access-denied-box" <?php if ($item->get_setting("hide-access-denied-box")) echo esc_attr("checked"); ?> data-target="hide-access-denied-box" class="setting" />
        </td>
      </tr>
      <tr>
        <td><?php esc_html_e("Access denied text", "cluevo"); ?></td>
        <td>
          <input type="text" name="access-denied-text" value="<?php echo esc_attr($item->get_setting("access-denied-text")); ?>" data-target="access-denied-text" class="setting" />
        </td>
      </tr>
    </table>
  </div>
  
<?php }


add_filter('cluevo_output_module_type', 'cluevo_output_module_type');
add_action('cluevo_tree_item_settings', 'cluevo_add_tree_item_close_button_setting');
add_action('cluevo_tree_item_settings', 'cluevo_add_tree_empty_setting');
add_action('cluevo_tree_item_settings', 'cluevo_add_tree_item_is_link_setting');

function cluevo_add_tree_item_is_link_setting($item) {
  $link = $item->get_setting("item-is-link");
?>
<div class="meta-container item-is-link">
  <div class="label"><?php _e("Item is a link", "cluevo"); ?></div>
  <p class="help">
    <?php _e("This item links to some other content. Assigned modules won't start, users will be sent the entered link instead.", "cluevo"); ?>
  </p>
  <input type="url" name="item-is-link" value="<?php echo esc_attr($link); ?>" data-target="item-is-link" class="setting" placeholder="https://" />
  <label>
    <input type="checkbox" name="open-link-in-new-window" <?php if ($item->get_setting("open-link-in-new-window")) echo esc_attr("checked"); ?> data-target="open-link-in-new-window" class="setting" />
    <?php esc_html_e("Open link in new window", "cluevo"); ?>
  </label>
  </div>
<?php }

function cluevo_register_default_module_types($args) {

  $scorm12 = [
    'name' => __('SCORM 1.2', 'cluevo'),
    'icon' => plugins_url("/images/icon-module-ui-scorm-1-2_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Upload a SCORM module or enter a link to a SCORM module file.', 'cluevo'),
    'field' => 'mixed',
    'filter' => '.zip',
    'field-placeholder' => __('https://', 'cluevo'),
    'button-label' => __('select file', 'cluevo')
  ];

  $scorm2004 = [
    'name' => __('SCORM 2004', 'cluevo'),
    'icon' => plugins_url("/images/icon-module-ui-scorm-2004_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Upload a SCORM module or enter a link to a SCORM module file.', 'cluevo'),
    'field' => 'mixed',
    'filter' => '.zip',
    'field-placeholder' => __('https://', 'cluevo'),
    'button-label' => __('select file', 'cluevo')
  ];

  $audio = [
    'name' => __('Audio File', 'cluevo'),
    'icon' => plugins_url("/images/icon-module-ui-audio_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Upload a audio file or enter a link to a audio file.', 'cluevo'),
    'field' => 'mixed',
    'filter' => '.mp3,.wav,.webm',
    'field-placeholder' => __('', 'cluevo'),
    'button-label' => __('select audio file', 'cluevo')
  ];

  $video = [
    'name' => __('Video File', 'cluevo'),
    'icon' => plugins_url("/images/icon-module-ui-video_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Upload a video file or enter a link to a video file.', 'cluevo'),
    'field' => 'mixed',
    'filter' => '.mp4,.webm,.mpeg',
    'field-placeholder' => __('https://', 'cluevo'),
    'button-label' => __('select video file', 'cluevo')
  ];

  $oembed = [
    'name' => __('YouTube, Vimeo, etc.', 'cluevo'),
    'icon' => plugins_url("/images/icon_module-ui-oembed_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Install the CLUEVO Video Tutorial Manager extension to embed content from YouTube and many other sites.', 'cluevo'),
    'field' => null,
    'filter' => null,
    'field-placeholder' => null,
    'button-label' => null,
    'alt-type' => "cluevo-lms-extension-oembed",
    'alt-icon-class' => "extension",
    'alt-content' => '<p><a class="cluevo-btn cluevo-btn-primary cluevo-btn-install-type" href="' . esc_attr(admin_url('plugin-install.php?s=cluevo&tab=search&type=term')) . '">' . esc_html__("Install CLUEVO oEmbed extension", "cluevo") . '</a></p>'
  ];

  $gdocs = [
    'name' => __('Google Documents', 'cluevo'),
    'icon' => plugins_url("/images/icon-module-ui-gdocs_256x256.png", plugin_dir_path(__FILE__)),
    'description' => __('Install the CLUEVO Google Documents extension to use your Google Documents as modules in your LMS.', 'cluevo'),
    'field' => null,
    'filter' => null,
    'field-placeholder' => null,
    'button-label' => null,
    'alt-type' => "cluevo-lms-extension-gdocs",
    'alt-icon-class' => "extension",
    'alt-content' => '<p><a class="cluevo-btn cluevo-btn-primary cluevo-btn-install-type" href="' . esc_attr(admin_url('plugin-install.php?s=cluevo&tab=search&type=term')) . '">' . esc_html__("Install CLUEVO Google Docs extension", "cluevo") . '</a></p>'
  ];

  $args["types"][] = $scorm12;
  $args["types"][] = $scorm2004;
  $args["types"][] = $audio;
  $args["types"][] = $video;
  $args["types"][] = $oembed;
  $args["types"][] = $gdocs;
}

add_action('cluevo_register_module_types', 'cluevo_register_default_module_types', 0);

?>
