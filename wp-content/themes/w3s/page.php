<?php

    get_header();

    while(have_posts()) {
        the_post(); ?>
        <h2><?php the_title(); ?> this is page.php</h2>
        <?php the_content(); ?>
        
    <?php }

    get_footer();
?>
