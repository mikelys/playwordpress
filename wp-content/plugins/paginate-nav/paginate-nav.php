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
        //global $wp;
        
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
             $i = 0;
            // $homeurl = "\html\page\";

            while ($htmlPosts->have_posts()) : $htmlPosts->the_post();
              ++$i;
              
           ?>
            <li><a href="<?php 
                    // var_dump($i);
                  //   if ( $i == 1 ) {
                  //        get_home_url();
                   //   }
                    //  else{
                          get_blog_url(  $i );
                   // echo $i;
                      //   the_permalink( get_the_ID() );
                   //    echo get_the_ID();
                    // }  
                  ?>"><?php the_title(); ?></a></li>
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

function get_blog_url(  $page_num ) {
//    $components = preg_split( '/\//', $orig_url );
//    //var_dump($page_num);
 //   $count = count( $components );
 //   $str_num = (string)$page_num;
 //   if ( $count > 1 ) {
//       unset( $components[$count-1] ); 
 //   }
  //   $ret=implode('/\//', $components);
  // $pattern="2";
//   $orig_url =  preg_replace("/\d+\/$/", $page_num , $orig_url);
   global $wp;
   $link = home_url( $wp->request );

  // echo  home_url( $wp->request );
   if ( preg_match("/\d+$/", $link) ) {
      $link = preg_replace("/\d+$/", $page_num, $link );
   }else{
      $link = $link."/page/$page_num";
   }
   echo $link;
   // echo home_url();
    // echo "localhost/wordpress/html/mike";
}
