<?php
if (!defined("CLUEVO_ACTIVE")) exit;

function cluevo_get_the_lms_tree() {
  global $cluevo_tree;
  return $cluevo_tree;
}

function cluevo_get_the_lms_user() {
  global $cluevo;
  return $cluevo->user;
}

function cluevo_get_the_lms_user_level() {
  $user = cluevo_get_the_lms_user();

  return $user->level["current"];
}

function cluevo_get_the_lms_user_exp() {
  $user = cluevo_get_the_lms_user();

  return $user->level['exp'];
}

function cluevo_get_the_lms_user_exp_next() {
  $user = cluevo_get_the_lms_user();

  return $user->level['next'];
}

function cluevo_get_the_lms_user_exp_remaining() {
  $user = cluevo_get_the_lms_user();

  return $user->level['remaining'];
}

function cluevo_get_the_lms_user_exp_pct() {
  $user = cluevo_get_the_lms_user();

  return $user->level['pct'];
}

function cluevo_get_the_lms_user_title() {
  $user = get_the_lms_user();

  return $user->level['title'];
}

function cluevo_the_lms_user_level() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['current']);
}

function cluevo_the_lms_user_exp() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['exp']);
}

function cluevo_the_lms_user_exp_next() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['next']);
}

function cluevo_the_lms_user_exp_remaining() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['remaining']);
}

function cluevo_the_lms_user_exp_pct() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['pct']);
}

function cluevo_the_lms_user_title() {
  $user = cluevo_get_the_lms_user();

  echo esc_html($user->level['title']);
}

function cluevo_has_lms_user_title() {
  $user = cluevo_get_the_lms_user();
  return !empty($user->level['title']);
}

function cluevo_have_lms_items() {
  global $cluevo;
  if (!empty($cluevo)) {
    return $cluevo->have_items();
  } else {
    return false;
  }
}

function cluevo_have_visible_lms_items() {
  global $cluevo;
  if (!empty($cluevo)) {
    return $cluevo->have_visible_items();
  } else {
    return false;
  }
}

function cluevo_the_lms_item_is_visible() {
  global $cluevo;
  if (!empty($cluevo)) {
    return ($cluevo->item->access || $cluevo->item->access_status["access_level"] > 0);
  } else {
    return false;
  }
}

function cluevo_the_lms_item($intId = null) {
  global $cluevo;
  return $cluevo->the_item();
}

function cluevo_get_the_lms_item_title() {
  global $cluevo;
  return $cluevo->item['metadata']->post_title;
}

function cluevo_the_lms_item_title() {
  echo esc_html(cluevo_get_the_lms_item_title());
}

function cluevo_the_lms_item_metadata() {
  global $cluevo;
  return $cluevo->the_item_metadata();
}

function cluevo_get_the_lms_item_type() {
  global $cluevo;
  return $cluevo->the_item_type();
}

function cluevo_the_lms_item_type() {
  global $cluevo;
  echo esc_attr($cluevo->the_item_type());
}

function cluevo_the_lms_tree() {
  global $cluevo;
  return $cluevo->tree;
}

function cluevo_have_lms_modules() {
  global $cluevo;
  return $cluevo->have_modules();
}

function cluevo_the_lms_module() {
  global $cluevo;
  return $cluevo->the_module();
}

function cluevo_the_lms_module_metadata() {
  global $cluevo;
  return $cluevo->the_module_metadata();
}

function cluevo_get_the_lms_module_progress() {
  global $cluevo;
  return $cluevo->the_module_progress();
}

function cluevo_the_lms_module_progress() {
  global $cluevo;
  echo esc_attr($cluevo->the_module_progress());
}

function cluevo_the_lms_module_progress_pct() {
  global $cluevo;
  echo esc_html($cluevo->the_module_progress() * 100 . "%");
}

function cluevo_the_lms_item_string_path() {
  global $cluevo;
  if (cluevo_in_the_lms_dependency_loop())
    return implode(' / ', $cluevo->dependency['path']['string']);
  else
    return implode(' / ', $cluevo->item['path']['string']);
}

function cluevo_the_lms_item_title_path() {
  global $cluevo;
  $ids = [];
  if (cluevo_in_the_lms_dependency_loop())
    $ids = $cluevo->dependency['path']['id'];
  else
    $ids = $cluevo->item['path']['id'];

  $parts = [];
  foreach ($ids as $id) {
    $parts[] = $cluevo->tree[$id]['metadata']->post_title;
  }

  return implode(' / ', $parts);

}

function cluevo_in_the_lms_dependency_loop() {
  global $cluevo;
  return $cluevo->in_the_dependency_loop;
}

function cluevo_get_the_lms_page() {
  global $cluevo;
  if (!empty($cluevo)) {
    return $cluevo->current_page;
  }
}

function cluevo_get_the_parent_lms_page() {
  global $cluevo;
  if (!empty($cluevo)) {
    if ($cluevo->shortcode)
      return false;

    if (!empty($cluevo->current_page->parent_id)) {
      $userId = (!empty($cluevo->user) && !empty($cluevo->user->ID)) ? $cluevo->user->ID : null;
      $metadataId = cluevo_get_metadata_id_from_item_id($cluevo->current_page->parent_id);
      if ($metadataId) {
        return get_post($metadataId);
      }
    } else {
      return get_page_by_title( 'Index', OBJECT, CLUEVO_PAGE_POST_TYPE);
    }
  }

  return null;
}

function cluevo_get_the_lms_item() {
  global $cluevo;
  if ($cluevo->in_the_dependency_loop)
    return $cluevo->dependency;
  else
    return $cluevo->item;
}

function cluevo_get_the_next_lms_item() {
  global $wpdb, $cluevo;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT item_id FROM $table WHERE parent_id = %d AND sort_order > %d ORDER BY sort_order ASC LIMIT 1";
  $result = $wpdb->get_var(
    $wpdb->prepare($sql, [ $cluevo->current_page->parent_id, $cluevo->current_page->sort_order ] )
  );

  if (!empty($result)) {
    return cluevo_get_learning_structure_item($result, get_current_user_id());
  }
}

function cluevo_get_the_previous_lms_item() {
  global $wpdb, $cluevo;
  $table = $wpdb->prefix . CLUEVO_DB_TABLE_TREE;
  $sql = "SELECT item_id FROM $table WHERE parent_id = %d AND sort_order < %d ORDER BY sort_order DESC LIMIT 1";
  $result = $wpdb->get_var(
    $wpdb->prepare($sql, [ $cluevo->current_page->parent_id, $cluevo->current_page->sort_order ] )
  );

  if (!empty($result)) {
    return cluevo_get_learning_structure_item($result, get_current_user_id());
  }
}

function cluevo_get_the_lms_item_path() {
  global $cluevo;
  $item = null;
  if ($cluevo->in_the_dependency_loop)
    $item = $cluevo->dependency;
  else
    $item = $cluevo->item;

  return cluevo_get_string_path($item->path);

}

function cluevo_have_lms_dependencies() {
  global $cluevo;
  return $cluevo->have_dependencies();
}

function cluevo_the_lms_dependency() {
  global $cluevo;
  return $cluevo->the_dependency();
}

function cluevo_get_the_lms_item_metadata() {
  global $cluevo;
  return $cluevo->the_item_metadata();
}

function cluevo_load_lms_item($intId) {
  global $cluevo;
  $cluevo->load_item($intId);
}

function cluevo_load_lms_module($strModule) {
  global $cluevo;
  $cluevo->load_module($strModule);
}

function cluevo_create_lms_loop($items) {
  global $cluevo;
  $cluevo->init_loop($items);
}

function cluevo_get_the_module_progress($strModule) {
  global $cluevo;
  if (!empty($cluevo->user)) {
    $key = sanitize_key(SOURCENOVA_LMS_USER_SCORE_SCALED_META_KEY . $strModule);
    if (array_key_exists($key, $cluevo->user['meta']))
      return $cluevo->user['meta'][$key];
  }
  return 0;
}

function cluevo_get_the_lms_module() {
  global $cluevo;
  return $cluevo->module;
}

function cluevo_get_the_lms_users_competence_scores() {
  global $cluevo;
  if (!empty($cluevo->user)) {
    return $cluevo->user->competences;
  } else {
    return [];
  }
}

function cluevo_get_item_progress_value() {
  $progressValue = 0;
  $item = cluevo_get_the_lms_item();
  //echo "<pre>";
  //print_r($item);
  //echo "</pre>";
  if (empty($item->module) || $item->module < 0) {
    $progressValue = count($item->completed_children);
  } else {
    $user = cluevo_get_the_lms_user();
    if (!empty($user)) {
      $progressValue = cluevo_get_users_best_module_attempt($user->ID, $item->module_id);
    }
  }

  return $progressValue;
}

function cluevo_get_item_progress_max() {
  $progressMax = 0;
  $item = cluevo_get_the_lms_item();
  if (empty($item->module) || $item->module < 0) {
    $progressMax = count($item->children);
  } else {
    $user = cluevo_get_the_lms_user();
    if (!empty($user)) {
      $progressMax = 1;
    }
  }

  return $progressMax;
}

function cluevo_get_item_progress_width() {
  $progressMax = cluevo_get_item_progress_max();
  $progressValue = cluevo_get_item_progress_value();
  $progressWidth = 0;

  if ($progressMax > 0)
    $progressWidth = ($progressValue / $progressMax) * 100;

  return $progressWidth;
}

function cluevo_display_notice($strTitle, $strMessage, $strType = 'notice', $dismissible = false) { ?>
  <div class="cluevo-notice cluevo-notice-<?php echo esc_attr($strType); ?><?php echo ($dismissible) ? " is-dismissible" : ""; ?>">
    <p class="cluevo-notice-title"><?php echo esc_html($strTitle); ?></p>
    <p><?php echo esc_html($strMessage); ?></p>
  </div>
<?php }

function cluevo_display_notice_html($strTitle, $strMessage, $strType = 'notice', $dismissible = false) { ?>
  <div class="cluevo-notice cluevo-notice-<?php echo esc_attr($strType); ?><?php echo ($dismissible) ? " is-dismissible" : ""; ?>">
    <p class="cluevo-notice-title"><?php echo esc_html($strTitle); ?></p>
    <p><?php echo $strMessage; ?></p>
  </div>
<?php }

function cluevo_item_has_parent_item() {
  $parentPost = cluevo_get_the_parent_lms_page();
  return (!empty($parentPost));
}

function cluevo_user_has_item_access_level() {
  $item = cluevo_get_the_lms_page();
  if (!empty($item)) {
    return ($item->access_status["access_level"] == true);
  }
}

function cluevo_get_the_items_module() {
  $item = cluevo_get_the_lms_page();
  $module = cluevo_get_module($item->module_id);
  return $module;
}

function cluevo_get_the_items_module_display_mode() {
  $item = cluevo_get_the_lms_item();
  if (!empty($item->display_mode)) {
    return $item->display_mode;
  } else {
    return strtolower(get_option("cluevo-modules-display-mode", "Lightbox"));
  }
}

function cluevo_get_the_content_list_style() {
  $cluevoListStyle = !empty($_COOKIE["cluevo-content-list-style"]) ? $_COOKIE["cluevo-content-list-style"] : null;
  $cluevoValidListStyles = ["cluevo-content-list-style-row", "cluevo-content-list-style-col"];
  $cluevoListStyle = (in_array($cluevoListStyle, $cluevoValidListStyles)) ? $cluevoListStyle : "cluevo-content-list-style-col";

  return $cluevoListStyle;
}

function cluevo_the_content_list_style() {
  echo cluevo_get_the_content_list_style();
}

function cluevo_get_the_breadcrumbs() {
  $item = cluevo_get_the_lms_page();
  if (empty($item)) return;
  $parts = explode('/', $item->path);
  $index = null;
  $list = [];
  global $cluevo;
  $userId = $cluevo->user_id;
  $structs = cluevo_get_learning_structures($userId);
  if (!empty($structs)) {
    foreach ($structs as $s) {
      $children = cluevo_get_learning_structure_item_children($s->item_id, $userId);
      $s->children = $children;
      $list[0][] = $s;
    }
  }
  if (!empty($parts)) {
    foreach ($parts as $part) {
      if (!empty($part)) {
        $cur = cluevo_get_learning_structure_item($part, $userId);
        $children = cluevo_get_learning_structure_item_children($part, $userId);
        $cur->children = $children;
        $list[] = $cur;
      }
    }
  }
  //die(print_r($list));
  return $list;
}

function cluevo_get_the_index_page() {
  if (($page = get_page_by_title( 'Index', OBJECT, CLUEVO_PAGE_POST_TYPE)) != NULL ) {
    return $page;
  }
  return false;
}

function cluevo_get_the_index_page_link() {
  $page = cluevo_get_the_index_page();
  if (!empty($page)) {
    //die(print_r($page));
    $link = get_permalink($page);
    return $link;
    var_dump($link);
  }
  return false;
}

function cluevo_get_the_shortcode_content() {
  global $cluevo;
  return $cluevo->shortcode_content;
}

function cluevo_user_has_competence_areas() {
  global $cluevo;
  if (empty($cluevo)) return false;
  if (empty($cluevo->user)) return false;
  return $cluevo->user->has_competence_areas();
}

function cluevo_the_users_competence_area() {
  global $cluevo;
  return $cluevo->user->the_competence_area();
}

function cluevo_get_the_users_competence_area() {
  global $cluevo;
  return $cluevo->user->competence_area;
}

function cluevo_get_the_users_competence_area_id() {
  global $cluevo;
  return $cluevo->user->competence_area->competence_area_id;
}

function cluevo_get_the_users_competence_area_name() {
  global $cluevo;
  return $cluevo->user->competence_area->competence_area_name;
}

function cluevo_get_the_users_competence_area_score() {
  global $cluevo;
  return $cluevo->user->competence_area->score;
}

function cluevo_get_the_users_competence_area_metadata_page() {
  global $cluevo;
  return get_post($cluevo->user->competence_area->metadata_id);
}

function cluevo_user_has_competences() {
  global $cluevo;
  if (empty($cluevo)) return false;
  if (empty($cluevo->user)) return false;
  return $cluevo->user->has_competences();
}

function cluevo_the_users_competence() {
  global $cluevo;
  return $cluevo->user->the_competence();
}

function cluevo_get_the_users_competence() {
  global $cluevo;
  return $cluevo->user->competence;
}

function cluevo_get_the_users_competence_name() {
  global $cluevo;
  return $cluevo->user->competence->competence_name;
}

function cluevo_get_the_users_competence_id() {
  global $cluevo;
  return $cluevo->user->competence->competence_id;
}

function cluevo_get_the_users_competence_score() {
  global $cluevo;
  return $cluevo->user->competence->score;
}

function cluevo_get_the_users_competences_metadata_page() {
  global $cluevo;
  return get_post($cluevo->user->competence->metadata_id);
}

function cluevo_users_competence_has_modules() {
  global $cluevo;
  if (empty($cluevo)) return false;
  if (empty($cluevo->user)) return false;
  return $cluevo->user->competence_has_modules();
}

function cluevo_the_users_competence_module() {
  global $cluevo;
  return $cluevo->user->the_competence_module();
}

function cluevo_get_the_users_competence_module() {
  global $cluevo;
  $score = $cluevo->user->competence_module->score;
  $module = cluevo_get_module($cluevo->user->competence_module->id);
  $module->competence_score = $score;
  return $module;
}

function cluevo_get_the_users_competence_module_score() {
  global $cluevo;
  return $cluevo->user->competence_module->score;
}

function cluevo_get_the_users_competence_module_coverage() {
  global $cluevo;
  return $cluevo->user->competence_module->coverage;
}


function cluevo_get_the_users_competence_module_id() {
  global $cluevo;
  return $cluevo->user->competence_module->id;
}

function cluevo_get_the_users_competence_module_metadata_page() {
  global $cluevo;
  $module = cluevo_get_module($cluevo->user->competence_module->id);
  return get_post($module->metadata_id);
}

function cluevo_get_the_lms_items_hide_info_box_setting() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $value = $item->get_setting("hide-info-box");
  return ((int)$value === 1);
}

function cluevo_get_the_lms_items_empty_text() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $text = trim($item->get_setting("element-is-empty-text"));
  return (!empty($text)) ? $text : __("This element is empty.", "cluevo");
}

function cluevo_the_lms_items_empty_text() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $text = $item->get_setting("element-is-empty-text");
  echo (!empty($text)) ? esc_html($text) : __("This element is empty.", "cluevo");
}

function cluevo_get_the_lms_items_hide_access_denied_box_setting() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $value = $item->get_setting("hide-access-denied-box");
  return ((int)$value === 1);
}

function cluevo_get_the_lms_items_access_denied_text() {
  global $cluevo;
  if (!empty($cluevo->item)) {
    $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
    $item->load_settings();
    $text = trim($item->get_setting("access-denied-text"));
    return (!empty($text)) ? $text : __("You do not have the required permissions to access this element", "cluevo");
  } else {
    return __("You do not have the required permissions to access this element", "cluevo");
  }
}

function cluevo_the_lms_items_access_denied_text() {
  global $cluevo;
  $item = (!empty($item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $text = $item->get_setting("access-denied-text");
  echo (!empty($text)) ? esc_html($text) : __("You do not have the required permissions to access this element", "cluevo");
}

function cluevo_the_item_is_a_link() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $link = trim($item->get_setting("item-is-link"));
  return ($link !== "") ? true : false;
}

function cluevo_the_items_link_opens_in_new_window() {
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  return ($item->get_setting("open-link-in-new-window") == 1) ? true : false;
}

function cluevo_get_the_items_link() {
  $meta = cluevo_the_lms_item_metadata();
  global $cluevo;
  $item = (!empty($cluevo->item)) ? $cluevo->item : $cluevo->current_page;
  $item->load_settings();
  $link = trim($item->get_setting("item-is-link"));
  if ($link !== "") return $link;
  if ($meta) return get_permalink($meta->ID);
}

?>
