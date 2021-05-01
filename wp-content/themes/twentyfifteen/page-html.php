<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */

get_header(); ?>

	<div id="primary" class="content-area">
			<?php
          $ourCurrentPage = get_query_var('paged');
          $htmlPosts = new WP_Query(array(
            'category_name' => 'html',
            'post_per_page' => 1,
            'paged' => $ourCurrentPage
          ));

        ?>

<div class="paginate-class-1">
			<p class="paginate-prev-1">
			  <?php
	            previous_posts_link('Previous');
	          ?>
	        </p>
	        <p class="paginate-next-1">
	          <?php  
	            next_posts_link('Next', $htmlPosts->max_num_pages); 
	          ?>
	        </p>  
        </div><main id="main" class="site-main" role="main">
                <?php
		// Start the loop.
		while ( $htmlPosts->have_posts() ) : $htmlPosts->the_post();
			// Include the page content template.
			get_template_part( 'content', 'page' );
		        //the_template_part('content', get_post_format());
		        //the_title();
                        //the_content();


		// End the loop.
		endwhile;
		?>
		<div class="paginate-class clearfix">
			<p class="paginate-prev">
			  <?php
	            previous_posts_link('Previous');
	          ?>
	        </p>
	        <p class="paginate-next">
	          <?php  
	            next_posts_link('Next', $htmlPosts->max_num_pages); 
	          ?>
	        </p>  
        </div>
		<?php

		wp_reset_postdata();
		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
