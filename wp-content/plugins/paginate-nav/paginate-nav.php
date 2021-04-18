<?php
/**
 * Plugin Name: Pagination Nav Plugin
 * Plugin URI: http://wordpress/pagination-nav
 * Description: List paginated navigation menu
 * Version: 1.0
 * Author: glarky
 * Author URI: http://glarky.com
 */ 

add_action( 'the_content', 'my_thank_you_text' );

function my_thank_you_text( $content ) {
    return $content = '<p>Thank you for reading!</p>';
}


