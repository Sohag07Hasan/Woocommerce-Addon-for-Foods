<?php
/*
 * Delivery Date widget
 */

class WooDeliveryTimeWidget extends WP_Widget{
	
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
		$this->woo_widget_cssclass = 'woocommerce widget_delivery_time';
		$this->woo_widget_description = __( 'Shows a date picker and specific times from database to filter', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_delivery_time';
		$this->woo_widget_name = __( 'WooCommerce Addon: Delivery Time', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('delivery_time', $this->woo_widget_name, $widget_ops);
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
		if (!isset($new_instance['title']) || empty($new_instance['title'])) $new_instance['title'] = __( 'Select a Delivery Time', 'woocommerce' );
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
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
		global $wpdb;
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'woocommerce' ) ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
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
		
		//fetcheing widget based data from database
		/*
		$terms_ids = get_terms(WooShopCustomizerA::food_provider_taxonomy, array('fields' => 'ids'));
		$delivery_times = array();
		
		if($terms_ids){
			foreach($terms_ids as $term_id){
				
				//rush hour
				$times = get_field(WooShopCustomizerA::$custom_fields['delivery_time_rush_hour'], WooShopCustomizerA::food_provider_taxonomy . '_' . $term_id);
				if($times){
					foreach($times as $t){
						$delivery_times[] = $t;
					}
				}
				
				//non rush hour
				$times = get_field(WooShopCustomizerA::$custom_fields['delivery_time_non_rush_hour'], WooShopCustomizerA::food_provider_taxonomy . '_' . $term_id);
					if($times){
						foreach($times as $t){
							$delivery_times[] = $t;
						}
					}
				}
		}
		
		$delivery_times = array_unique($delivery_times);		
		asort($delivery_times);
		*/
		
		$delivery_times = self::get_times();
		
		$title = $instance['title'];
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		echo $before_widget . $before_title . $title . $after_title;
		
		?>
		
			<form action="" method="post">
				<input readonly style="width: 100px;" size="20" type="text" name="delivery_time" id="datepicker" value="<?php echo $_SESSION['delivery']['delivery_time']; ?>" /> Date <br/><br/>
				<select id="delivery_time_selector" name="delivery_time_h" style="width: 70px; height: 25px;"> Time  
					<?php 
						if($delivery_times){

							$now = current_time('timestamp');
							$deadline = $now - 1*60*60;
							
							$is_today = $_SESSION['delivery']['is_today'];
							$date = $_SESSION['delivery']['delivery_time'];
												
							
							foreach($delivery_times as $t){
								$generated_time_stamp = strtotime($date . ' ' . $t);
								if($is_today){
									if($deadline > $generated_time_stamp){
										?>
										<option <?php selected($_SESSION['delivery']['delivery_time_h'], $t); ?> value="<?php echo $t; ?>"> <?php echo $t; ?> </option>
										<?php 
										}
									}
									else{
										?>
										<option <?php selected($_SESSION['delivery']['delivery_time_h'], $t); ?> value="<?php echo $t; ?>"> <?php echo $t; ?> </option>
										<?php 
									}							
							}
						}
					?>
				</select>
				<input style="width: 50px; text-align: center;" type="submit" value="go"> 
			</form>
			
			<script type="text/javascript">
				//jQuery('#datepicker').datepicker({dateFormat: "yy-mm-dd", minDate: 0});
				//jQuery('#datepicker').datepicker({dateFormat: "yy-mm-dd"});
			</script>
		
		<?php 
		
		echo $after_widget;
	}
	
	
	
	static function get_times(){
		$terms_ids = get_terms(WooShopCustomizerA::food_provider_taxonomy, array('fields' => 'ids'));
		$delivery_times = array();
		
		if($terms_ids){
			foreach($terms_ids as $term_id){
		
				//rush hour
				$times = get_field(WooShopCustomizerA::$custom_fields['delivery_time_rush_hour'], WooShopCustomizerA::food_provider_taxonomy . '_' . $term_id);
				if($times){
					foreach($times as $t){
						$delivery_times[] = $t;
					}
				}
		
				//non rush hour
				$times = get_field(WooShopCustomizerA::$custom_fields['delivery_time_non_rush_hour'], WooShopCustomizerA::food_provider_taxonomy . '_' . $term_id);
				if($times){
					foreach($times as $t){
						$delivery_times[] = $t;
					}
				}
			}
		}
		
		$delivery_times = array_unique($delivery_times);
		asort($delivery_times);
		
		return $delivery_times;
	}
}