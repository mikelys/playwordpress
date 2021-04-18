<div class="cluevo-content-list">
<?php
global $cluevo;
while (cluevo_have_lms_dependencies()) {
  $item = cluevo_the_lms_dependency();
  cluevo_display_template('part-tree-item');
?>
<?php } ?>
</div>
