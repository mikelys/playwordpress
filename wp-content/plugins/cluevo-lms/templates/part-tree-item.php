<?php
$item = cluevo_get_the_lms_item();
if ($item->access_level == 0) return;
$item->load_settings();
$path = cluevo_get_the_lms_item_path();
$path = implode(" / ", $path);
$meta = cluevo_the_lms_item_metadata();
$progressMax = cluevo_get_item_progress_max();
$progressValue = cluevo_get_item_progress_value();
$progressWidth = cluevo_get_item_progress_width();
$completedModules = 0;
$moduleCount = $item->module_count;
$user = cluevo_get_the_lms_user();

$img = null;
if (!empty($meta)) {
  if (has_post_thumbnail($meta->ID))
    $img = get_the_post_thumbnail($meta->ID);
}

if (empty($img)) {
  $imgDir = cluevo_get_conf_const('CLUEVO_IMAGE_URL');
  $img = '<img src="' . "$imgDir/lms-content-placeholder.jpg" . '" alt="" />';
}

$displayMode = cluevo_get_the_items_module_display_mode();
$tileMode = strtolower(get_option("cluevo-display-diagonal-tiles", "off"));
$diagonal = ($tileMode === "on") ? "diagonal" : "";
$blocked = ($item->access_level < 2 || !$item->access) ? "blocked" : "";

$module = null;
if (!empty($item->module) && $item->module > 0) {
  if ($item->module) {
    $module = cluevo_get_module($item->module);
    do_action('cluevo_enqueue_module_scripts');
  }
}

$data = [];
foreach ($item->settings as $key => $value) {
  if (is_array($value) && count($value) == 1) {
    $value = maybe_unserialize($value[0]);
  } else {
    $value = maybe_unserialize($value);
  }
  if (!empty($value)) {
    if (!is_string($value)) {
      $value = json_encode($value);
    }
    $key = str_replace(CLUEVO_META_DATA_PREFIX, '', $key);
    $key = str_replace('_', '-', $key);
    $data[] = "data-" . $key . "=\"" . esc_attr($value) . "\"";
  }
}

$dataString = implode($data, ' ');

?>
  <div class="cluevo-content <?php echo $item->type; ?> <?php echo !$item->published ? "draft" : ''; ?>">
<?php if (!$blocked) { ?>
  <?php if (cluevo_the_item_is_a_link()) { ?>
    <a
      class="cluevo-content-item-link <?php echo $item->type; ?>"
      <?php if (cluevo_the_items_link_opens_in_new_window()) echo 'target="_blank"'; ?>
      href="<?php echo esc_attr(cluevo_get_the_items_link()); ?>"
    >
  <?php } else { ?>
    <a
      class="cluevo-content-item-link <?php echo ($module === null && $item->type == "module") ? "cluevo-empty-module" : ""; ?> <?php echo $item->type; ?> <?php echo $blocked; ?> <?php if (!empty($item->module) && $item->module > 0 && $module !== null) { echo "cluevo-module-link cluevo-module-mode-$displayMode"; } ?>"
      <?php if (cluevo_the_item_is_a_link() && cluevo_the_items_link_opens_in_new_window()) echo 'target="_blank"'; ?>
      href="<?php echo esc_attr(cluevo_get_the_items_link()); ?>"
      data-item-id="<?php echo esc_attr($item->item_id); ?>"
      <?php echo $dataString; ?>
      data-module-id="<?php echo (!empty($module->module_id)) ? $module->module_id : 0; ?>"
      <?php if ($item->get_setting('hide-lightbox-close-button') == 1) echo "data-hide-lightbox-close-button=\"1\""; ?>
      data-module-type="<?php echo esc_attr(strtolower( ((!empty($module->type_name)) ? $module->type_name : "" ))); ?>"
    >
  <?php } ?>
<?php } else {  ?>
  <div class="cluevo-content-item-link <?php if (!$item->access) echo "access-denied"; ?>" data-access-denied-text="<?php echo esc_attr(cluevo_get_the_lms_items_access_denied_text()); ?>">
<?php } ?>
    <div class="cluevo-post-thumb">
      <div class="cluevo-item-type-corner <?php esc_attr_e($item->type); ?>"></div>
      <?php if (!empty($img)) { echo $img; } ?>
      <div class="cluevo-meta-bg"><div class="meta-bg-corner"></div></div>
        <div class="cluevo-meta-container">
          <div class="cluevo-meta-item cluevo-access"><?php if ($item->access) { ?><i class="fas fa-unlock"></i><?php } else { ?><i class="fas fa-lock"></i> <?php } ?></div>
          <div class="cluevo-meta-item"><?php if (!empty($item->completed) && $item->completed) { ?><i class="fas fa-check"></i><?php } ?></div>
          <?php if (!empty($module)) { ?>
          <div class="cluevo-badge cluevo-module-type"><img title="<?php echo esc_attr(ucfirst($module->type_name)); ?>" src="<?php echo cluevo_get_conf_const('CLUEVO_IMAGE_URL') . "icon-" . sanitize_file_name($module->type_name) . ".svg"; ?>" /></div>
          <?php } ?>
          <?php if ($item->type !== 'module') { ?>
          <div class="cluevo-module-status cluevo-meta-item">
            <?php if (count($item->children) > 0) echo count($item->completed_children) . " / " . count($item->children) ; ?>
          </div>
          <?php } ?>
          <div class="cluevo-item-type">
          <?php _e($item->type, "cluevo"); ?> <?php if (!$item->published) echo "[" . esc_html__('Draft', "cluevo") . "]"; ?>
          </div>
        </div>
    </div>
    <div class="cluevo-content-container">
      <div class="cluevo-description"><?php echo (!empty($meta->post_title)) ? $meta->post_title : "&nbsp;"; ?></div>
      <div class="cluevo-excerpt"><?php echo (!empty($meta->post_excerpt)) ? $meta->post_excerpt: "&nbsp;"; ?></div>
    </div>
    <div class="cluevo-progress-container">
      <span
        class="cluevo-progress"
        style="width: <?php echo 100 - $progressWidth; ?>%;"
        data-value="<?php echo $progressValue;?>"
        data-max="<?php echo $progressMax; ?>"
      ></span>
    </div>
<?php if (!$blocked) { ?>
  </a>
<?php } else { ?>
</div>
<?php } ?>
</div>
