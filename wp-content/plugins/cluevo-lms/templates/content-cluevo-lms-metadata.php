<?php
get_header();

global $post;
global $cluevo;
if (!empty($cluevo) && !empty($cluevo->current_page) && !empty($cluevo->current_page->metadata_id) && $cluevo->current_page->metadata_id == $post->ID) {
  $item = $cluevo->current_page;
} else {
  $item = cluevo_get_learning_structure_item_from_metadata_id($post->ID);
}
$levels = CLUEVO_LEARNING_STRUCTURE_LEVELS;

if (!empty($item)) {
?>
<div class="cluevo-content-area-container">
  <div id="primary" class="cluevo content-area">
    <main id="main" class="site-main">
      <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        </header><!-- .entry-header -->
        <div class="entry-content">
        <?php
        if (!empty($item)) {
          $template = "cluevo-tree-item-" . $item->type;
          if (file_exists(cluevo_get_conf_const('CLUEVO_PLUGIN_PATH') . "/templates/$template.php")) {
            cluevo_display_template($template);
          } else {
            cluevo_display_template('cluevo-tree-item');
          }
        } else {
          cluevo_display_template('cluevo-course-index');
        }
        ?>
        </div> <!-- entry content -->
      </article>
    </main><!-- #main -->
  </div><!-- #primary -->
</div>

<?php
}
get_footer();
?>
