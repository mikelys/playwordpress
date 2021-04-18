<div class="cluevo-back-link-container">
  <div class="cluevo-content-list-style-switch">
    <div class="cluevo-btn cluevo-content-list-style-col <?php echo (cluevo_get_the_content_list_style() === 'cluevo-content-list-style-col') ? "active" : ""; ?>"><?php include( cluevo_get_conf_const('CLUEVO_IMAGE_DIR') . "icon-cols.svg"); ?></div>
    <div class="cluevo-btn cluevo-content-list-style-row <?php echo (cluevo_get_the_content_list_style() === 'cluevo-content-list-style-row') ? "active" : ""; ?>"><?php include( cluevo_get_conf_const('CLUEVO_IMAGE_DIR') . "icon-rows.svg"); ?></div>
  </div>
</div>
<div class="cluevo-content-list <?php cluevo_the_content_list_style(); ?>">
<?php
if (cluevo_have_lms_items() && cluevo_have_visible_lms_items()) {
  while (cluevo_have_lms_items()) {
    cluevo_the_lms_item();
    cluevo_display_template('part-tree-item');
  }
} else {
  if (current_user_can('administrator')) {
    cluevo_display_notice(
      __("Notice", "cluevo"),
      __("The course index is empty. You can add courses through the admin area.", "cluevo")
    );
  } else {
    cluevo_display_notice(
      __("Notice", "cluevo"),
      __("The course index is empty or you do not have the required permissions to access this page", "cluevo")
    );
  }
}
?>
</div>
