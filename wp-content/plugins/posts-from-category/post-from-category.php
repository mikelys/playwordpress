<?php
/*
 * Plugin Name: Posts From Category
 * Version: 4.0.2
 * Plugin URI: http://wordpress.org/plugins/posts-from-category/
 * Description: Plugin to display posts from specific category. It comes with multiple layout option to display post list. Option to select category or exclude post is available wgich provide advance filter to your requirement. You can enable or disable posted date, thumbnail, read more button and more easily from widget. 
 * Author: Manesh Timilsina
 * Author URI: https://www.wpmanesh.com/
 * License: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */


$dir = plugin_dir_path( __FILE__ );

require_once( $dir.'functions.php' );

class PFCWidget extends WP_Widget {
	
	/**
	* Declares the PFCWidget class.
	*
	*/	
	public function __construct() {

		global $control_ops, $post_cat, $post_num, $post_length;

		load_plugin_textdomain( 'PFC', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action( 'wp_enqueue_scripts', array( $this, 'pfc_load_frontend_scripts' ), 5 );

		$widget_ops = array(						
						'classname' 	=> 'pfc-widget', 
						'description' 	=> esc_html__( 'Display posts from selected category', 'PFC') 
					);

		parent::__construct('PFCWidget', esc_html__( 'MT: Posts From Category', 'PFC' ), $widget_ops, $control_ops);

		$this->alt_option_name = 'widget_pfc';	
	}

	function pfc_load_frontend_scripts() {

		wp_enqueue_style( 'pfc-style', rtrim( plugin_dir_url( __FILE__ ), '/' ) . '/assets/pfc-style.css' );

		wp_enqueue_script( 'pfc-custom', rtrim( plugin_dir_url( __FILE__ ), '/' ) . '/assets/pfc-custom.js', array( 'jquery' ), '4.0.1' );
	}
	
	/**
	* Displays the Widget
	*
	*/
	function widget($args, $instance){

		$title 			= apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		$layout 		= ! empty( $instance['layout'] ) ? $instance['layout'] : '';
		
		$post_cat 		= ! empty( $instance['post_cat'] ) ? $instance['post_cat'] : 0;

		$post_order_by	= ! empty( $instance['post_order_by'] ) ? $instance['post_order_by'] : '';

		$post_order		= ! empty( $instance['post_order'] ) ? $instance['post_order'] : '';

		$post_num 		= ! empty( $instance['post_num'] ) ? $instance['post_num'] : 5;

		$post_exclude 	= ! empty( $instance['post_exclude'] ) ? $instance['post_exclude'] : '';

		$post_length 	= !empty( $instance['post_length'] ) ? $instance['post_length'] : '';

		$readmore_text 	= ! empty( $instance['readmore_text'] ) ? $instance['readmore_text'] : '';

		$date    		= ! empty( $instance['date'] ) ? $instance['date'] : false;

		$thumbnail		= ! empty( $instance['thumbnail'] ) ? $instance['thumbnail'] : false;

		$post_thumbs	= ! empty( $instance['post_thumbs'] ) ? $instance['post_thumbs'] : '';	

		echo $args['before_widget']; ?>

		<div class="pfc-posts-wrap">

			<?php  

			if ( ! empty( $title ) ) {

				echo $args['before_title'] . esc_html( $title ). $args['after_title'];

			} ?>

			<div class="pfc-posts-inner">

				<?php 

				pfc_call_shortcode_details( $layout, $post_cat, $post_order_by, $post_order, $post_num, $post_exclude, $post_length, $readmore_text, $date, $thumbnail, $post_thumbs  );

				 ?>
			</div>
		</div>
		<?php
		echo $args['after_widget'];
	}	
	
	/**
	* Saves the widgets settings.
	*
	*/
	function update($new_instance, $old_instance){

		$instance 					= $old_instance;

		$instance['title']          = sanitize_text_field( $new_instance['title'] );

		$instance['layout']         = sanitize_text_field( $new_instance['layout'] );	

		$instance['post_cat'] 		= absint( $new_instance['post_cat'] );

		$instance['post_order_by'] 	= sanitize_text_field( $new_instance['post_order_by'] );

		$instance['post_order'] 	= sanitize_text_field( $new_instance['post_order'] );

		$instance['post_num'] 		= absint( $new_instance['post_num'] );

		$instance['post_exclude'] 	= sanitize_text_field( $new_instance['post_exclude'] );

		$instance['post_length'] 	= absint( $new_instance['post_length'] );

		$instance['readmore_text'] 	= sanitize_text_field( $new_instance['readmore_text'] );

		$instance['date']     		= (bool) $new_instance['date'] ? true : false;

		$instance['thumbnail'] 		= (bool) $new_instance['thumbnail'] ? true : false;		

		$instance['post_thumbs'] 	= sanitize_text_field( $new_instance['post_thumbs'] );

		return $instance;
	}

	/**
	* Creates the edit form for the widget.
	*
	*/
	function form($instance){	
		
		$instance = wp_parse_args( (array) $instance, array(
			'title'			=> '', 
			'layout'		=> 'layout-one', 
			'post_cat'		=> '', 
			'post_order_by'	=> 'date', 
			'post_order'	=> 'DESC',
			'post_num'		=> 5, 
			'post_exclude'	=> '', 
			'post_length'	=> 10, 
			'readmore_text' => esc_html__( 'Read More', 'PFC' ),
			'date'			=> true, 
			'thumbnail'		=> true, 
			'post_thumbs'	=> 'thumbnail', 
			) 
		); 
		?>

		<p>
		    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'PFC' ); ?></label>
		    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

        <p>
          <label for="<?php echo  esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Select Layout:', 'PFC' ); ?></label>
        </p>

		<p> 
		    <input type="radio" <?php checked( $instance[ 'layout' ], 'layout-one' ); ?> id="<?php echo $this->get_field_id( 'layout-one' ); ?>" name="<?php echo $this->get_field_name('layout'); ?>" value="layout-one" />
		    <label for="<?php echo $this->get_field_id( 'layout-one' ); ?>"><img src="<?php echo plugins_url( 'assets/images/layout-one.png', __FILE__ ); ?>" class="layout-one"></label>

		    <input type="radio" <?php checked( $instance[ 'layout' ], 'layout-two' ); ?> id="<?php echo $this->get_field_id( 'layout-two' ); ?>" name="<?php echo $this->get_field_name('layout'); ?>" value="layout-two" />
		    <label for="<?php echo $this->get_field_id( 'layout-two' ); ?>"><img src="<?php echo plugins_url( 'assets/images/layout-two.png', __FILE__ ); ?>" class="layout-two"></label> 
		</p>


        <p>
          <label for="<?php echo  esc_attr( $this->get_field_id( 'post_cat' ) ); ?>"><?php esc_html_e( 'Select Category:', 'PFC' ); ?></label>
			<?php
            $cat_args = array(
                'orderby'         => 'name',
                'hide_empty'      => 1,
                'class' 		  => 'widefat',
                'taxonomy'        => 'category',
                'name'            => $this->get_field_name( 'post_cat' ),
                'id'              => $this->get_field_id( 'post_cat' ),
                'selected'        => absint( $instance['post_cat'] ),
                'show_option_all' => esc_html__( 'All Categories','PFC' ),
              );
            wp_dropdown_categories( $cat_args );
			?>
        </p>

        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'post_order_by' ) ); ?>"><?php esc_html_e( 'Order By:', 'PFC' ); ?></label>
            <?php
            $this->dropdown_post_order_by( array(
                'id'       => $this->get_field_id( 'post_order_by' ),
                'name'     => $this->get_field_name( 'post_order_by' ),
                'selected' => esc_attr( $instance['post_order_by'] ),
                )
            );
            ?>
        </p>

        <p>
          <label for="<?php echo esc_attr( $this->get_field_id( 'post_order' ) ); ?>"><?php esc_html_e( 'Order:', 'PFC' ); ?></label>
            <?php
            $this->dropdown_post_order( array(
                'id'       => $this->get_field_id( 'post_order' ),
                'name'     => $this->get_field_name( 'post_order' ),
                'selected' => esc_attr( $instance['post_order'] ),
                )
            );
            ?>
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'post_num' ) ); ?>"><?php esc_html_e( 'Number of Posts:', 'PFC' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'post_num' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_num' ) ); ?>" type="number" value="<?php echo absint( $instance['post_num'] ); ?>" />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'post_exclude' ) ); ?>"><?php esc_html_e( 'Exclude Posts:', 'PFC' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'post_exclude' ) ); ?>" name="<?php echo  esc_attr( $this->get_field_name( 'post_exclude' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['post_exclude'] ); ?>" />
            <small><?php esc_html_e( 'Enter post id separated with comma to exclude multiple posts', 'PFC' ); ?></small>
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_name('post_length') ); ?>">
                <?php esc_html_e('Excerpt Length:', 'PFC'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('post_length') ); ?>" name="<?php echo esc_attr( $this->get_field_name('post_length') ); ?>" type="number" value="<?php echo absint( $instance['post_length'] ); ?>" />
            <small><?php esc_html_e( 'Use 0 to hide Excerpt/Desc', 'PFC' ); ?></small>
        </p>

        <p>
        	<label for="<?php echo esc_attr( $this->get_field_id( 'readmore_text' ) ); ?>"><?php esc_html_e( 'Read More Text:', 'PFC' ); ?></label>
        	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'readmore_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'readmore_text' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['readmore_text'] ); ?>" />
        	<small><?php esc_html_e( 'Leave this field empty to hide read more button', 'PFC' ); ?></small>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( $instance['date'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'date' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'date' ) ); ?>"><?php esc_html_e( 'Show Posted Date', 'PFC' ); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked( $instance['thumbnail'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'thumbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail' ) ); ?>" />
            <label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail' ) ); ?>"><?php esc_html_e( 'Display/Select Thumbnail', 'PFC' ); ?></label>
        </p>
		
		<p>		
			<?php
			$this->dropdown_post_thumbnail( array(
			    'id'       => $this->get_field_id( 'post_thumbs' ),
			    'name'     => $this->get_field_name( 'post_thumbs' ),
			    'selected' => esc_attr( $instance['post_thumbs'] ),
			    )
			); 
			?>  
		</p>

		<?php		
	
	} //end of form
	
	
	function dropdown_post_order_by( $args ) {
	    $defaults = array(
	        'id'       => '',
	        'class'    => 'widefat',
	        'name'     => '',
	        'selected' => 0,
	    );

	    $r = wp_parse_args( $args, $defaults );
	    $output = '';

		$choices =	array( 
			'author' 		=> esc_html__('Author', 'PFC'),
			'title' 		=> esc_html__('Post Title', 'PFC'),
			'ID' 			=> esc_html__('Post ID', 'PFC'),
			'date' 			=> esc_html__('Date', 'PFC'),							
			'menu_order' 	=> esc_html__('Menu Order', 'PFC'),						
			'comment_count' => esc_html__('Number of Comments', 'PFC'),
			'rand' 			=> esc_html__('Random', 'PFC')
		);

	    if ( ! empty( $choices ) ) {

	        $output = "<select name='" . esc_attr( $r['name'] ) . "' id='" . esc_attr( $r['id'] ) . "' class='" . esc_attr( $r['class'] ) . "'>\n";
	        foreach ( $choices as $key => $choice ) {
	            $output .= '<option value="' . esc_attr( $key ) . '" ';
	            $output .= selected( $r['selected'], $key, false );
	            $output .= '>' . esc_html( $choice ) . '</option>\n';
	        }
	        $output .= "</select>\n";
	    }

	    echo $output;
	}

	function dropdown_post_order( $args ) {
	    $defaults = array(
	        'id'       => '',
	        'class'    => 'widefat',
	        'name'     => '',
	        'selected' => 0,
	    );

	    $r = wp_parse_args( $args, $defaults );
	    $output = '';

		$choices =	array( 
			'ASC' 	=> esc_html__('Ascending', 'PFC'),
			'DESC' 	=> esc_html__('Descending', 'PFC')
		);

	    if ( ! empty( $choices ) ) {

	        $output = "<select name='" . esc_attr( $r['name'] ) . "' id='" . esc_attr( $r['id'] ) . "' class='" . esc_attr( $r['class'] ) . "'>\n";
	        foreach ( $choices as $key => $choice ) {
	            $output .= '<option value="' . esc_attr( $key ) . '" ';
	            $output .= selected( $r['selected'], $key, false );
	            $output .= '>' . esc_html( $choice ) . '</option>\n';
	        }
	        $output .= "</select>\n";
	    }

	    echo $output;
	}

	function dropdown_post_thumbnail( $args ) {
	    $defaults = array(
	        'id'       => '',
	        'class'    => 'widefat',
	        'name'     => '',
	        'selected' => 0,
	    );

	    $r = wp_parse_args( $args, $defaults );
	    
	    $output = '';

	    global $_wp_additional_image_sizes;

	    $get_intermediate_image_sizes = get_intermediate_image_sizes();

		$choices = array( 
			'thumbnail' => esc_html__('Thumbnail', 'PFC'),
			'medium' 	=> esc_html__('Medium', 'PFC'),
			'large' 	=> esc_html__('Large', 'PFC'),
			'full' 		=> esc_html__('Full (Original)', 'PFC')
		);

	    $show_dimension = true;

	    if ( true === $show_dimension ) {
	        foreach ( array( 'thumbnail', 'medium', 'large' ) as $key => $_size ) {
	            $choices[ $_size ] = $choices[ $_size ] . ' (' . get_option( $_size . '_size_w' ) . 'x' . get_option( $_size . '_size_h' ) . ')';
	        }
	    }

	    if ( ! empty( $_wp_additional_image_sizes ) && is_array( $_wp_additional_image_sizes ) ) {
	        foreach ( $_wp_additional_image_sizes as $key => $size ) {
	            $choices[ $key ] = $key;
	            if ( true === $show_dimension ) {
	                $choices[ $key ] .= ' (' . $size['width'] . 'x' . $size['height'] . ')';
	            }
	        }
	    }

	    if ( ! empty( $allowed ) ) {
	        foreach ( $choices as $key => $value ) {
	            if ( ! in_array( $key, $allowed ) ) {
	                unset( $choices[ $key ] );
	            }
	        }
	    }

	    if ( ! empty( $choices ) ) {

	        $output = "<select name='" . esc_attr( $r['name'] ) . "' id='" . esc_attr( $r['id'] ) . "' class='" . esc_attr( $r['class'] ) . "'>\n";
	        foreach ( $choices as $key => $choices ) {
	            $output .= '<option value="' . esc_attr( $key ) . '" ';
	            $output .= selected( $r['selected'], $key, false );
	            $output .= '>' . esc_html( $choices ) . '</option>\n';
	        }
	        $output .= "</select>\n";
	    }

	    echo $output;
	}
	
}// END class
	
/**
* Register  widget.
*
* Calls 'widgets_init' action after widget has been registered.
*/
function pfcwidget_init() {
	
	register_widget('PFCWidget');

}	

add_action('widgets_init', 'pfcwidget_init');

// Shortcode added
function pfc_init_shortcode( $atts ) {

    extract( shortcode_atts( 
        array( 
        	'layout'    	=> 'layout-one',
        	'cat'       	=> '',
        	'order_by'  	=> 'date',
        	'order'     	=> 'DESC',
        	'post_number'   => 5,
        	'exclude'       => '',
        	'length'       	=> 10,
        	'readmore'      => esc_html__('Read More', 'PFC'),
        	'show_date'     => true,
        	'show_image'    => true,
        	'image_size'    => 'thumbnail',
        ), 
        $atts 
    ));

    ob_start();

    pfc_call_shortcode_details( $layout, $cat, $order_by, $order, $post_number, $exclude, $length, $readmore, $show_date, $show_image, $image_size );

    return ob_get_clean();
}

add_shortcode( 'pfc', 'pfc_init_shortcode' );

// Shortcode function details
function pfc_call_shortcode_details( $layout, $post_cat, $order_by, $order, $post_num, $exclude, $desc_length, $readmore_text, $show_date, $show_thumbnail, $thumb_size  ){

	if( 'layout-one' == $layout ){

		$layout_class = 'layout-one';

	}else{

		$layout_class = 'layout-two';

	}

	$exclude_id = explode(',', $exclude);
		
	$p_args = array( 
				'orderby' 				=> $order_by, 
				'order' 				=> $order, 
				'no_found_rows'         => true,
				'post__not_in'          => get_option( 'sticky_posts' ),
				'ignore_sticky_posts'   => true,
				'post_status'           => 'publish',
				'posts_per_page' 		=> absint( $post_num ),
				'post__not_in' 			=> $exclude_id 
			);

	if ( absint( $post_cat ) > 0 ) {

		$p_args['cat'] = absint( $post_cat );
		
	}
	
	$p_query = new WP_Query( $p_args );

	if($p_query->have_posts()){ ?>

		<div class="pfc-posts-main">

			<?php 

			while($p_query->have_posts()){

				$p_query->the_post(); ?>

				<div class="pfc-post <?php echo $layout_class; ?>">
				
					<?php 

					if( 'true' == $show_thumbnail && has_post_thumbnail() ){ ?>

						<div class="news-thumb">

							<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( $thumb_size ); ?></a>

						</div>
						<?php 
					}

					if( 'true' == $show_thumbnail && has_post_thumbnail() ){

						$align_class = 'info-with-space';

					}else{

						$align_class = 'info-without-space';

					} ?>

					<div class="news-text-wrap <?php echo $align_class; ?>">

					    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

					    <?php 

					    if( 'true' == $show_date ){  ?>
					    	<span class="posted-date"><?php echo esc_html( get_the_date() ); ?></span>
					    	<?php 
					    } 

					    if( 0 != absint( $desc_length ) ){

					    	$desc_content = pfc_custom_limit_words( absint($desc_length) );
					    	 
					    	echo wp_kses_post($desc_content) ? wpautop( wp_kses_post($desc_content) ) : '';
	                        
	                	}

	                	if( !empty( $readmore_text ) ){ ?>
	                		<a href="<?php the_permalink(); ?>" class="read-more"><?php echo esc_html( $readmore_text ); ?></a>
	                	<?php }

					    ?>
					     
					</div><!-- .news-text-wrap -->

				</div>

				<?php 

			}

			wp_reset_postdata(); ?>

		</div>

		<?php 

	}

}

// Add cutome button to editor
add_action( 'init', 'pfc_init_editor_button_add' );
 
if ( ! function_exists( 'pfc_init_editor_button_add' ) ) {

    function pfc_init_editor_button_add() {

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
            return;
        }
 
        if ( get_user_option( 'rich_editing' ) !== 'true' ) {
            return;
        }
 
        add_filter( 'mce_external_plugins', 'pfc_add_custom_editor_button' );

        add_filter( 'mce_buttons', 'pfc_register_custom_editor_button' );
    }
}
 
if ( ! function_exists( 'pfc_add_custom_editor_button' ) ) {

    function pfc_add_custom_editor_button( $plugin_array ) {

        $plugin_array['pfcbutton'] = rtrim( plugin_dir_url( __FILE__ ), '/' ) . '/assets/shortcode-btn.js';

        return $plugin_array;

    }

}
 
if ( ! function_exists( 'pfc_register_custom_editor_button' ) ) {

    function pfc_register_custom_editor_button( $buttons ) {

        array_push( $buttons, 'pfcbutton' );

        return $buttons;

    }
}
 
add_action ( 'after_wp_tiny_mce', 'pfc_tinymce_custom_vars' );
 
if ( !function_exists( 'pfc_tinymce_custom_vars' ) ) {

	function pfc_tinymce_custom_vars() {

		$cat_args = array(
			'orderby'         => 'name',
			'hide_empty'      => 1,
			'taxonomy'        => 'category',
		);

		$cat_count = 1;

		$cat_params[0] = array(
			'text' 	=> esc_html__( 'Select Category', 'PFC' ), 
			'value'	=> 0 
		);

		$post_cats = get_categories( $cat_args );
		
		foreach ( $post_cats as $cat ) {

			$cat_params[$cat_count]['text']   = $cat->name;
			
			$cat_params[$cat_count]['value']  = $cat->term_id;
			
			$cat_count++;
		}

		//For image sizes
		$image_size[0] = array(
			'text' 	=> esc_html__( 'Select Image Size', 'PFC' ), 
			'value'	=> ''
		);

	    global $_wp_additional_image_sizes;

	    $get_intermediate_image_sizes = get_intermediate_image_sizes();

		$choices = array( 
			'thumbnail' => esc_html__('Thumbnail', 'PFC'),
			'medium' 	=> esc_html__('Medium', 'PFC'),
			'large' 	=> esc_html__('Large', 'PFC'),
			'full' 		=> esc_html__('Full (Original)', 'PFC')
		);

	    $show_dimension = true;

	    if ( true === $show_dimension ) {
	        foreach ( array( 'thumbnail', 'medium', 'large' ) as $key => $_size ) {
	            $choices[ $_size ] = $choices[ $_size ] . ' (' . get_option( $_size . '_size_w' ) . 'x' . get_option( $_size . '_size_h' ) . ')';
	        }
	    }

	    if ( ! empty( $_wp_additional_image_sizes ) && is_array( $_wp_additional_image_sizes ) ) {
	        foreach ( $_wp_additional_image_sizes as $key => $size ) {
	            $choices[ $key ] = $key;
	            if ( true === $show_dimension ) {
	                $choices[ $key ] .= ' (' . $size['width'] . 'x' . $size['height'] . ')';
	            }
	        }
	    }

	    if ( ! empty( $allowed ) ) {
	        foreach ( $choices as $key => $value ) {
	            if ( ! in_array( $key, $allowed ) ) {
	                unset( $choices[ $key ] );
	            }
	        }
	    }

	    if ( ! empty( $choices ) ) {

	    	$img_count = 1;


	        foreach ( $choices as $key => $value ) {
	        	 
	           $image_size[$img_count]['text']   = $value;
	           
	           $image_size[$img_count]['value']  = $key;

	           $img_count++;
	        }
	        
		} ?>

		<script type="text/javascript">
			var tinyMCE_object = <?php echo json_encode(
				array(
					'button_name' 	=> esc_html__(' PFC ', 'PFC'),
					'button_title' 	=> esc_html__('Posts From Category', 'PFC'),
					'post_cat_list' => $cat_params,
					'image_sizes'   => $image_size,
				)
				);
			?>;
		</script><?php
	}
}