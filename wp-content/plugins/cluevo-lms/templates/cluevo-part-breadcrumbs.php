<?php
$crumbs = cluevo_get_the_breadcrumbs();
$item = cluevo_get_the_lms_page();
if (!empty($crumbs)) {
  echo "<div class=\"cluevo-crumb-container\">";
  foreach ($crumbs as $key => $c) {
    if (is_array($c)) {
      echo "<div class=\"cluevo-crumb\">";
      echo "<p class=\"cluevo-crumb-title\">";
      echo "<a href=\"" . cluevo_get_the_index_page_link() . "\">Index</a></p>";
      echo "<ul class=\"cluevo-crumb-children\">";
      foreach ($c as $child) {
        $active = ($child->item_id == $item->item_id) ? "active" : "";
        echo "<li class=\"$active\"><a href=\"" . esc_url(get_permalink($child->metadata_id)) . "\">" . $child->name . "</a></li>";
      }
      echo "</ul>";
      echo "</div>";
    } else {
      if ($c->access) {
        echo "<div class=\"cluevo-crumb-spacer\"><span class=\"dashicons dashicons-arrow-right-alt2\"></span></div>";
        echo "<div class=\"cluevo-crumb\">";
        echo "<p class=\"cluevo-crumb-title\">";
        echo "<a href=\"" . esc_url(get_permalink($c->metadata_id)) . "\">" . $c->name . "</a></p>";
        echo "<ul class=\"cluevo-crumb-children\">";
        foreach ($c->children as $child) {
          $active = ($child->item_id == $item->item_id) ? "active" : "";
          if ($child->access) {
            echo "<li class=\"$active\"><a href=\"" . esc_url(get_permalink($child->metadata_id)) . "\">" . $child->name . "</a></li>";
          }
        }
        echo "</ul>";
        echo "</div>";
      }
    }
  }
  echo "<div class=\"cluevo-crumb-spacer\"><span class=\"dashicons dashicons-arrow-right-alt2\"></span></div>";
  echo "<div class=\"cluevo-crumb\">";
  echo "<p class=\"cluevo-crumb-title\">" . $item->name . "</p>";
  echo "</div>";
  echo "</div>";
}

?>
