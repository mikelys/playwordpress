<?php 
/**
 * Plugin Name: Pagination Nav Plugin
 * Plugin URI: http://wordpress/pagination-nav
 * Description: List paginated navigation menu
 * Version: 1.0
 * Author: glarky
 * Author URI: http://glarky.com
 */ 

class list_Pagination_Nav extends WP_Widget {
    function __construct(){
        $widget_ops = array( 
            'classname' => 'list_pagination_nav', 
            'description' => 'display pagination nav'
        );
       parent::__construct( 'list_paginationi_nav', 'List Pagination', $widget_ops ); 
    }
    
    function widget( $args, $instance ) {
        extract($args, EXTR_SKIP);
        
        echo $before_widget;
        $title = empty($instance['title']) ? ' ' : apply_filters( 'widget_title', $instance['title'] );
        
        if (!empty($title))
           echo $before_title . $title . $after_title;;

        // WIDGET code goes here
        ?>
          <ul class="list-pagination-class">
          <?php //create and run custom loop
            $ourCurrentPage = get_query_var('paged'); 
            $htmlPosts = new WP_Query(array(
              'category_name' => 'html',  
              'posts_per_page' => -1,
              'paged' => $ourCurrentPage 
            ));
            while ($htmlPosts->have_posts()) : $htmlPosts->the_post();
           ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
            <?php
                  endwhile;
                  wp_reset_postdata(); 
            ?>
          </ul>
        <?php
         echo $after_widget;   
         
    }
}

add_action( 'widgets_init', 'list_pagination_nav' );

function list_pagination_nav() {
  register_widget( 'list_Pagination_Nav' );
}


