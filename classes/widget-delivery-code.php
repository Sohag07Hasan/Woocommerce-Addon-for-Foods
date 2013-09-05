<?php
/*
 * Delivery Date widget
 */

class WooDeliveryCodeWidget extends WP_Widget{
	
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;
	
	/**
	 * constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct(){
		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'woocommerce widget_delivery_code';
		$this->woo_widget_description = __( 'Allow customers to change delviery code anytime', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_delivery_code';
		$this->woo_widget_name = __( 'WooCommerce Addon: Delivery Code', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('delivery_code', $this->woo_widget_name, $widget_ops);
	}
	
	
	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __( 'Your Delivery Code', 'woocommerce' );
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['deliverypage'] = $new_instance['deliverypage'];
		return $instance;
	}


	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		
		$this->get_pages();
		
		global $wpdb;
		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'woocommerce' ) ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" />
			</p>
			<p>
				<label for="delivery_code_creator">Code registration page</label>
				<select class="widefat" name="<?php echo esc_attr( $this->get_field_name('deliverypage') ); ?>">
					<option value="0">Choose a Page</option>
					<?php 
						foreach($this->get_pages() as $page){
							?>
							<option value="<?php echo $page->ID; ?>" <?php selected($page->ID, $instance['deliverypage']); ?>  ><?php echo $page->post_title; ?></option>
							<?php 
						}
					?>
				</select>
			</p>
		<?php
	}
	
	
	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ){
		extract( $args );
				
		
		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget . $before_title . $title . $after_title;
		
		//var_dump($instance);
		$action = '';
		$shop_page = woocommerce_get_page_id('shop');
		$permalink = get_permalink($shop_page);
		if($permalink){
			$action = $permalink;
		}
		
		?>
		
			<form action="<?php echo $action; ?>" method="post">
				<input type="hidden" name="change_delivery_code_form" value="Y" />
				<p> 
					<input style="width: 85px;" type="text" name="change_delivery_code" value="<?php echo $_SESSION['delivery']['delivery_code']; ?>" /> &nbsp; &nbsp;
					<input type="submit" value="Change" />
				</p>
				 				
			</form>
						
			<p> <a target="_blank" href="<?php echo get_permalink($instance['deliverypage']); ?>"> Don't have a code? Get one </a> </p>
		<?php 
		
		echo $after_widget;
	}
	
	
	
	/**
	 * get all the puglished page
	 * @wp_query
	 * id and page name
	 * */
	function get_pages(){
		$args = array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'orderby' => 'title',
			'posts_per_page' => -1
		);
		
		$results = new WP_Query($args);
		return $results->posts;
		
	}
	
}