<?php
$user = cluevo_get_the_lms_user();
$displayMode = strtolower(get_option("cluevo-modules-display-mode", "Iframe"));
cluevo_get_users_competences(1);
if (!empty($user) && !empty($user->ID)) {
get_header();
?>
<div class="cluevo-content-area-container">
  <div id="primary" class="cluevo content-area">
    <main id="main" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php if (!empty($user)) { ?>
    <header class="cluevo-learner-name">
    <h1 class="entry-title"><?php echo esc_html($user->display_name); ?></h1>
    <?php cluevo_display_template('part-exp-title'); ?>
    </header><!-- .entry-header -->
    <div class="entry-content">
      <p><?php echo esc_html__("This page shows you a summary of your learning progress.", "cluevo"); ?></p>
      <h2><?php echo esc_html__("Competences", "cluevo"); ?></h2>
      <div class="cluevo-user-competences-container cluevo-content-list">
        <?php $comps = cluevo_get_the_lms_users_competence_scores(); ?>
        <?php if (!empty($comps)) { foreach ($comps as $c) { ?>
        <?php
        $img = null;
        if (has_post_thumbnail($c->metadata_id)) {
          $img = get_the_post_thumbnail($c->metadata_id);
        }
        if (empty($img)) {
          $imgDir = cluevo_get_conf_const('CLUEVO_IMAGE_URL');
          $img = '<img src="' . "$imgDir/lms-content-placeholder.jpg" . '" alt="" />';
        }
        ?>
          <div class="cluevo-competence-container cluevo-content">
            <a class="cluevo-content-item-link" href="<?php echo get_permalink($c->metadata_id); ?>">
              <div class="cluevo-post-thumb">
                <?php if (!empty($img)) { echo $img; } ?>
                <!-- <div class="skew-container"> -->
                  <div class="cluevo-meta-bg"><?php include( plugin_dir_path( __DIR__ ) . "images/triangleRB-LT.svg"); ?></div>
                  <div class="cluevo-meta-container">
                    <?php if (!empty($c->modules) && count($c->modules) > 0) { ?> <p class="cluevo-competence-toggle-modules">&#128712;</p> <?php } ?>
                  </div>
              </div>
              <div class="cluevo-content-container">
              <div class="cluevo-description">
              <?php echo esc_html($c->competence_name); ?>
              <?php $tmpScore = (!empty($c->competence_score) && is_numeric($c->competence_score)) ? $c->competence_score : 0; ?>
              <p><?php echo $tmpScore * 100 ?>% / <?php echo (is_array($c->modules) ? count($c->modules) : 0) . " " .  esc_html__("Modules", "cluevo"); ?></p></div>
              </div>
              <div class="cluevo-progress-container">
                <span class="cluevo-progress" style="width: <?php echo esc_attr(100 - $tmpScore * 100); ?>%;" data-value="<?php echo esc_attr($tmpScore); ?>" data-max="1"></span>
              </div>
              <div class="cluevo-competence-modules">
                <?php $scores_found = false; ?>
                <?php if (!empty($c->modules)) { ?>
                  <ul>
                  <?php foreach ($c->modules as $m) { ?>
                    <?php $mScore = (!empty($m->competence_score) && is_numeric($m->competence_score)) ? $m->competence_score : 0; ?>
                    <?php if ($mScore) { ?>
                      <?php $scores_found = true; ?>
                      <li>
                        <div class="cluevo-competence-module-progress-container">
                          <p class="cluevo-comp-module-name"><?php echo esc_html($m->module_name); ?></p>
                          <p class="cluevo-comp-module-score"><?php echo esc_html(round($mScore * 100), 2); ?>% / <?php echo esc_html(round($m->competence_coverage * 100)); ?>%</p>
                        </div>
                        <div class="cluevo-progress-container">
                          <span class="cluevo-progress" style="width: <?php echo esc_attr(100 - (1 / ($m->competence_coverage / (($mScore) ? $mScore : 1))) * 100); ?>%;" data-value="<?php echo esc_attr($mScore); ?>" data-max="<?php echo esc_attr($m->competence_coverage); ?>"></span>
                        </div>
                      </li>
                    <?php } ?>
                  <?php } ?>
                  </ul>
                <?php } ?>
              </div>
            </a>
          </div>
        <?php } } ?>
      </div>
      <h2>Kurse</h2>
      <div class="cluevo-content-list">
      <?php
      while (cluevo_have_lms_items()) {
        cluevo_the_lms_item();
        cluevo_display_template('part-tree-item');
      }
      ?>
      </div>
      <?php } ?>
    </div> <!-- entry content -->
    </article>
    </main><!-- #main -->
  </div><!-- #primary -->
</div>
<?php } else { ?>
<?php
  wp_redirect(home_url('/cluevo-management/login/?redirect_to=' . get_permalink()));
  exit;
}
get_footer();
?>
