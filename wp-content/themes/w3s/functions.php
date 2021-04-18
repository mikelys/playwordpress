<?php

function w3s_files() {
	wp_enqueue_style('w3_css', get_template_directory_uri() . '/w3.css');
    wp_enqueue_style('w3s_main_styles', get_stylesheet_uri());
    	
}

add_action('wp_enqueue_scripts', 'w3s_files');
