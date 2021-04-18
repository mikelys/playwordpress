<?php
if (is_admin()) {
  /**
   * Outputs the selected course group
   *
   */
  function cluevo_render_learning_structure_ui() {
    $tab = (!empty($_GET["tab"]) && ctype_alpha($_GET["tab"])) ? cluevo_strip_non_alpha($_GET["tab"]) : CLUEVO_ADMIN_TAB_LMS_STRUCTURE;
    $page = CLUEVO_ADMIN_PAGE_LMS;

    $url = remove_query_arg(['tree_id', 'create-tree', 'tree-name', "create-metadata-page"]);
    $create_page = (!empty($_GET["create-metadata-page"]) && is_numeric($_GET["create-metadata-page"])) ? (int)$_GET["create-metadata-page"] : null;
    if (!empty($create_page)) {

      $item = cluevo_get_learning_structure_item($create_page);
      if (!empty($item)) {
        $tmpTree = [ $item->item_id => $item ];
        $newMeta = cluevo_create_metadata_post($item, $item->parent_id, $tmpTree);
        $tmpTree[$item->item_id]->metadata_id = $newMeta;
        if (!empty($tmpTree)) {
          foreach ($tmpTree as $item) {
            if (!empty($item->metadata_id)) {
              cluevo_update_learning_structure_item($item);
            }
          }
        }
      }
    }

    $treeIndex = 1;
    if (isset($_GET["add-demo-modules"])) {
      cluevo_install_demos();
      cluevo_js_redirect(admin_url("admin.php?page=$page&tab=$tab"));
    }

    $submit = (!empty($_POST["save-tree"]) && ctype_alpha($_POST["save-tree"])) ? sanitize_text_field($_POST["save-tree"]) : null;
    $treeFlat = (!empty($_POST["lms-tree-flat"]) && json_decode(urldecode($_POST["lms-tree-flat"]))) ? (string)$_POST["lms-tree-flat"] : null;
    if (!empty($submit) && !empty($treeFlat)) {
      cluevo_save_learning_structure($treeFlat);
    }

    $userId = get_current_user_id();

    $tree = cluevo_get_learning_structure_item(1);
    if (empty($tree)) {
      cluevo_create_default_tree();
      $tree = cluevo_get_learning_structure_item(1);
    }

    $treeName = $tree->name;

    $modules = cluevo_get_modules();

    $curTree = $tree;
  ?>
  <form method="post" id="tree-form" action="<?php echo esc_html(admin_url("admin.php?page=$page&tab=$tab")); ?>">
    <input type="hidden" name="page" value="<?php echo esc_attr(CLUEVO_ADMIN_PAGE_LMS); ?>" />
    <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>" />
    <input type="hidden" name="lms-tree-flat" value="" />
    <div class="course-group-selection-container">
      <label><?php esc_html_e("Name of the group", "cluevo"); ?>
        <input type="text" name="rename-tree" id="lms-tree-name-input" value="<?php echo esc_attr($treeName); ?>" />
      </label>
      <div class="cluevo-btn cluevo-btn-primary cluevo-form-submit-btn"><?php esc_attr_e("Save", "cluevo"); ?></div>
      <input type="hidden" name="save-tree" value="true" />
      <?php if (!empty($curTree->metadata_id)) { ?>
      <a href="<?php echo get_edit_post_link($tree->metadata_id); ?>" class="cluevo-btn edit-tree-metadata"><?php esc_html_e("Edit Post", "cluevo"); ?></a>
      <?php } ?>
    </div>
  <?php

    $optArr = cluevo_get_learning_structure_items($treeIndex, $userId);
    $tree = cluevo_build_tree($optArr, $treeIndex);
    $optJson = json_encode($tree);

    echo '<input type="hidden" name="lms-tree" value="" id="lms-tree" />';  // hidden field used to submit the course tree
    echo '<input type="hidden" name="lms-tree-flat" value="" id="lms-tree-flat" />';  // hidden field used to submit the course tree array
    echo '<input type="hidden" name="lms-tree-id" value="" id="lms-tree-id" />';  // hidden field used to submit the course tree array
    echo '<input type="hidden" name="lms-tree-name" value="" id="lms-tree-name" />';  // hidden field used to submit the course tree array

    echo '<script type="text/javascript">var courses = ' . $optJson . ';</script>';
    echo '<script type="text/javascript">var modules = ' . json_encode($modules) . ';</script>';
    echo '<script type="text/javascript">var shortcode = "' . CLUEVO_SHORTCODE . '";</script>';
    echo '<div id="cluevo-module-selector"></div>';

    // hidden item template for js cloning
    cluevo_render_courses(new CluevoItem(), $modules, "item-tpl");

    // hidden interval meta fields for js cloning
  ?>
    <div class="meta-container repeating global template">
      <div class="label"><?php esc_html_e("Module has to be repeated periodically", "cluevo"); ?></div>
      <p class="help">
        <?php esc_html_e("Defines in what interval users have to repeat the module.", "cluevo"); ?>
      </p>
      <div class="meta-input-field-container">
        <input type="number" min="0" value="0" data-target="repeat-interval"/>
        <select class="repeat-interval-type" data-target="repeat-interval-type">
          <?php foreach (CLUEVO_REPEAT_INTERVAL_TYPES as $key => $value) { ?>
          <option value="<?php echo esc_attr($key); ?>" <?php if ($key === 'day') echo 'selected="selected"'; ?>><?php echo esc_html($value); ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
  <?php

    $indexPage = get_page_by_title( 'Index', OBJECT, CLUEVO_PAGE_POST_TYPE);
    $courses = $tree; //json_decode($opt);
    echo "<h2>" . esc_html($treeName) . "</h2>";

    echo "<div class=\"cluevo-admin-notice cluevo-notice-info\">";
    echo "<p>" . __("The startpage of the learning management system can be found here: ", "cluevo") . "<a target=\"_blank\" href=\"" . get_post_permalink($indexPage->ID) . "\">" . esc_attr__("CLUEVO LMS", "cluevo") . "</a></p>";
    echo "<p>" . sprintf( __("The learning tree can be displayed on any page with %s.", "cluevo"), "<code>[" . CLUEVO_SHORTCODE . " item=\"" . esc_html($treeIndex) . "\"]</code>") . "</p>";
    echo "<p>" . __("You can copy the shortcode to any item by clicking on the item's id or the [s] icon.", "cluevo") . "</p>";
    echo "</div>";
    if (empty($courses)) {
      cluevo_display_notice_html(
        __("Notice", "cluevo"),
        __("No courses have been created yet. Click here to install our demo course and modules: ", "cluevo") . "<a href=\"" . esc_html(admin_url("admin.php?page=$page&tab=$tab&add-demo-modules")) . "\">" . __("Add demo course and modules", "cluevo") . "</a>",
        "info"
      );
    }

    echo '<ol id="level-1" class="sortable root course-structure" data-level="1" data-tree-id="' . esc_attr($treeIndex) . '" data-tree-name="' . esc_attr($treeName) . '">';
    if (!empty($courses) && is_array($courses)) {
      foreach ($courses as $key => $course) {
        cluevo_render_courses($course, $modules);
      }
    }
    echo "</ol>";
    echo "<div class=\"course-structure-buttons\">";
    $btnSize = (empty($courses)) ? "cluevo-inc-btn-size" : "";
    echo "<button class=\"cluevo-btn auto cluevo-btn-secondary add-course $btnSize\">" . esc_html__("Add Course", "cluevo") . "</button>";
    echo "</div>";
    echo "<hr />";
    echo "<div class=\"cluevo-course-structure-tools\">";
    echo "<p class=\"cluevo-add-demos\"><a href=\"" . esc_html(admin_url("admin.php?page=$page&tab=$tab&add-demo-modules")) . "\">" . __("Add demo course and modules", "cluevo") . "</a></p>";
    echo '<div id="reset-dependencies" class="cluevo-btn reset-dependencies">' . esc_attr__("Reset Dependencies", "cluevo") . '</div>';
    echo '<div class="cluevo-btn cluevo-btn-primary save-tree cluevo-form-submit-btn">' . esc_attr__("Save", "cluevo") . '</div>';
    echo "</div>";
    echo '<input type="hidden" name="save-tree" value="true" />';
    echo "</form>";
  }
}
?>
