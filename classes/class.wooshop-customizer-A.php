<?php
/**
 * Customize the woo shop with customization algorithm A
 * */


class WooShopCustomizerA{
	
	
	static $form_keys = array(
		'code' => 'Delivery Code',
		'used_by' => 'Delivery Code used By',
		'comapny_name' => 'Company Name',
		'group_name' => 'Group Name',
		'first_name' => 'Main Contact First Name',
		'last_name' => 'Main contact Last Name',
		'email' => 'Main Contact Email',
		'phone' => 'Main contact Phone',
		'shipping_address_1' => 'Shipping Address 1',
		'shipping_address_2' => 'Shipping Address 2',
		'location' => 'Deliverly Location',
		'state' => 'State',
		'city' => 'City',
		'zip' => 'Zip',
		'wp_user' => 'Wp User Id',
		'country' => 'Country'
	);
	
	static $shipping_keys = array(
		'comapny_name' => 'shipping_company',
		'first_name' => 'shipping_first_name',
		'last_name' => 'shipping_last_name',
		'shipping_address_1' => 'shipping_address_1',
		'shipping_address_2' => 'shipping_address_2',		
		'state' => 'shipping_state',
		'city' => 'shipping_city',
		'zip' => 'shipping_postcode',
		'country' => 'shipping_country'
	);
	
	
	
	
	
	//custom fields
	static $custom_fields = array(
		'address_1' => 'address_1',
		'city' => 'city',
		'state' => 'state',
		'zip' => 'zip_code',
		'max_delivery_distance' => 'max_delivery_distance',
		'min_order' => 'min_order',
		'max_delivery_crew' => 'max_delivery_crew',
		'avg_delivery_speed' => 'avg_delivery_speed',
		'max_prod_rate_per_rush_hour' => 'max_prod_rate_per_rush_hour',
		'max_prod_rate_per_non_rush_hour' => 'max_prod_rate_per_non_rush_hour',
		'delivery_time_rush_hour' => 'delivery_time_rush_hour',
		'delivery_time_non_rush_hour' => 'delivery_time_non_rush_hour',
		'store_phone' => 'store_phone',
		
	);
	
	
	static $term_details = array('sold_out' => array(), 'deal_on' => array());
	
	
	//provider taxonomy
	const food_provider_taxonomy = 'brands';
	
	
	
	
	static $shipping_default_values = array();
	
	
	//initialize the hooks
	static function init(){
		
		//trace the shop page and hook to insert the popup modal
		add_action('wp_enqueue_scripts', array(get_class(), 'enqueue_modal_popup'));
		add_action('highlycustomized_before_shop_loop', array(get_class(), 'insert_modal_into_shop_body'));
		add_action('woocommerce_before_single_product', array(get_class(), 'insert_modal_into_shop_body'));
		
		//add to cart button class modification based on session
		add_filter('add_to_cart_class', array(get_class(), 'add_to_cart_class'));
		add_filter('add_to_cart_url', array(get_class(), 'add_to_cart_url'));
		
			
		//add new section in form editing apge and update the new optiosn
		add_filter('frm_add_form_settings_section', array(get_class(), 'frm_add_form_settings_section'), 10, 2);
		add_filter('frm_form_options_before_update', array(get_class(), 'frm_form_options_before_update'), 10, 2);
		
		
		//popup form submission handler
		add_action('init', array(get_class(), 'process_delivery_code'), 0);			
		add_action('init', array(get_class(), 'force_to_cart'), 10000);
		
		// set the shipping address from delivery code
		add_action('woocommerce_before_checkout_shipping_form', array(get_class(), 'set_default_shipping_address'));
				
		
		//modify the query parameer based on delivery code
		add_action('woocommerce_product_query', array(get_class(), 'woocommerce_product_query'), 100, 2);		

		//widget
		add_action( 'widgets_init', array(get_class(), 'register_delivery_widget'));
		
		//syncronize table
	//	register_activation_hook(WOOSHOPCUSTOMIZER_FILE, array(get_class(), 'sync_db_main'));
		
	//	add_action('init', array(get_class(), 'sync_db'));
				
		//ajax handling
		add_action('wp_ajax_superstore_date_changed', array(get_class(), 'superstore_date_changed'));
		add_action('wp_ajax_nopriv_superstore_date_changed', array(get_class(), 'superstore_date_changed'));
	}
	
	
	
	
	//syncronizing database table
	static function sync_db_main(){
		global $WooShopDb;
		return $WooShopDb->sync_db();
	}
	
	
	
	/**
	 *Register the delivery widget 
	 */
	static function register_delivery_widget(){
		register_widget('WooDeliveryTimeWidget');
		register_widget('WooDeliveryCodeWidget');		
	}	
			
	
	/*
	 * if delivery code not found it will change the class
	 * */
	
	static function add_to_cart_class($class){
		global $product;
		
		if(!empty($_SESSION['delivery']['delivery_code']) && !empty($_SESSION['delivery']['delivery_time']) && !empty($_SESSION['delivery']['delivery_time_h'])){
			return $class;			
		}
		else{
			return 'create_a_delivery_code';
		}

		
		
	}
	
	
	//if delivery code not found it will change the url
	static function add_to_cart_url($url){
		if(!empty($_SESSION['delivery']['delivery_code']) && !empty($_SESSION['delivery']['delivery_time']) && !empty($_SESSION['delivery']['delivery_time_h'])){
			return $url;
		}
		else{
			$url = remove_query_arg('add-to-cart', $url);
		}
		
		return $url;
	}
	
	
	//auto fill the shipping address
	static function autofill_shipping_address($address_fields){
		var_dump($address_fields);
		return $address_fields;
	}
	
	
	
	/*
	 * default shipping address
	 * */
	static function set_default_shipping_address($checkout){
		if(!empty($_POST)) return;
				
		if(isset($_SESSION['delivery'])){
			global $frm_form, $frmdb, $wpdb;
			
			$delivery = $_SESSION['delivery'];
			
			$delivery_code = $delivery['delivery_code'];					
			$delivery_form_id = $delivery['delivery_form_id'];
			$delivery_code_field_id = $delivery['delivery_code_field_id'];
			
			$item_values = self::get_item_metas_by_item_id($delivery_code, $delivery_code_field_id);
			$form = $frm_form->getOne($delivery_form_id);
			
			/*
			var_dump($item_values);
			var_dump($form->options['deliverycode']);
			die();
			*/
			foreach(self::$shipping_keys as $key => $shipping_key){
				
				
				$item_id = $item_values[$form->options['deliverycode'][$key]['self']];			
				$field_id = $form->options['deliverycode'][$key]['child'];
								
				if(in_array($key, array('state', 'city'))){					
					self::$shipping_default_values[$shipping_key] = $wpdb->get_var("select meta_value from $frmdb->entry_metas where field_id = '$field_id' and item_id = '$item_id'");
				}
				else{
					self::$shipping_default_values[$shipping_key] = $item_values[$form->options['deliverycode'][$key]['self']];
				}
							
			}
				
						
		}
	}
	
	
	
	//get the zip code from the delivery code
	static function get_zip_code_from_delivery_code(){
		global $frm_form, $frmdb, $wpdb;
			
		$delivery = $_SESSION['delivery'];
		
		$delivery_code = $delivery['delivery_code'];					
		$delivery_form_id = $delivery['delivery_form_id'];
		$delivery_code_field_id = $delivery['delivery_code_field_id'];
		
		$item_values = self::get_item_metas_by_item_id($delivery_code, $delivery_code_field_id);
		$form = $frm_form->getOne($delivery_form_id);
		
		//look at the default shipping keys
		$key = 'zip';
		return $item_values[$form->options['deliverycode'][$key]['self']];
	}
	
	
	/**
	 * get the form data using child and self key
	 * */
	static function get_entry_with_self_child($self, $child){
		global $frm_form, $frmdb, $wpdb;
		return $wpdb->get_var("select meta_value from $frmdb->entry_metas where field_id like '$child' and item_id like '$self'");
	}
	
	
	
	//get shipping address from delivery code
	static function get_street_address_from_delivery_code(){
		global $frm_form, $frmdb, $wpdb;
			
		$delivery = $_SESSION['delivery'];
		
		$delivery_code = $delivery['delivery_code'];					
		$delivery_form_id = $delivery['delivery_form_id'];
		$delivery_code_field_id = $delivery['delivery_code_field_id'];
		
		$item_values = self::get_item_metas_by_item_id($delivery_code, $delivery_code_field_id);
		$form = $frm_form->getOne($delivery_form_id);
		
		//look at the default shipping keys
		$key = 'shipping_address_1';
		
		//street address
		$address[] = $item_values[$form->options['deliverycode'][$key]['self']];
			
		//city and state
		$address[] = self::get_entry_with_self_child($item_values[$form->options['deliverycode']['city']['self']], $form->options['deliverycode']['city']['child']);
		$address[] = self::get_entry_with_self_child($item_values[$form->options['deliverycode']['state']['self']], $form->options['deliverycode']['state']['child']);
		
		return implode(', ', $address);
	}
	
	
	//get item id from item meta table
	static function get_item_metas_by_item_id($meta_value, $field_id){
		global $frmdb, $wpdb;
		
		//$sql = "select item_id from $frmdb->entry_metas where meta_value like '$meta_value' and field_id like '$field_id' limit 1";
		$sql = "select meta_value, field_id from $frmdb->entry_metas where item_id like (select item_id from $frmdb->entry_metas where meta_value like '$meta_value' and field_id like '$field_id' limit 1)";
		$results = $wpdb->get_results($sql);
		
		$array = array();
		
		if($results){
			foreach($results as $result){
				$array[$result->field_id] = $result->meta_value;
			}
		}
		
		return $array;
	}
		
	
	//if shop/product archive page then include the lean modal
	static function enqueue_modal_popup(){
		//if(is_shop()){			
			wp_register_script('woocommmerce-shop-reveal-modal', self::get_url('asset/reveal/jquery.reveal.js'), array('jquery'));
			wp_enqueue_script( 'woocommmerce-shop-reveal-modal' );
			
			wp_register_style('woocommmerce-shop-reveal-style', self::get_url('asset/reveal/reveal.css'));
			wp_enqueue_style('woocommmerce-shop-reveal-style');
			
			//driver
			wp_register_script('woocommerce-shop-lean-modal-driver', self::get_url('js/popup-handler.js'));
			wp_enqueue_script('woocommerce-shop-lean-modal-driver');
			wp_localize_script('woocommerce-shop-lean-modal-driver', "SuperstoreAjax", array('ajax_url' => admin_url('admin-ajax.php')));
			
			wp_register_style('woocommmerce-shop-master-style', self::get_url('css/master-style.css'));
			wp_enqueue_style('woocommmerce-shop-master-style');
			
			
			//date picker including
			wp_register_script('woocommerce-shop-date-picker-js', self::get_url('asset/date-picker/js/jquery-ui-1.10.3.custom.min.js'));
			wp_enqueue_script('woocommerce-shop-date-picker-js');
			wp_register_style('woocommerce-shop-date-picker-css', self::get_url('asset/date-picker/css/ui-lightness/jquery-ui-1.10.3.custom.min.css'));
			wp_enqueue_style('woocommerce-shop-date-picker-css');
			
		//}
	}
	
	
	/*utility to get different script location (http)*/
	static function get_url($path = ''){
		return WOOSHOPCUSTOMIZER_URL . '/' . $path;
	}
	
	
	//function to insert html for modal
	static function insert_modal_into_shop_body(){
		
		//die();
		
		//shop page popup
		if(empty($_SESSION['delivery']['delivery_code']) && empty($_SESSION['delivery']['delivery_city'])){		
			include WOOSHOPCUSTOMIZER_DIR . '/includes/shop-page-popup.php';	
		}
		
		//add button will create a popup if delviery code in not found
		if(empty($_SESSION['delivery']['delivery_code'])){
			include WOOSHOPCUSTOMIZER_DIR . '/includes/popup-code-creator.php';
		}
		elseif (empty($_SESSION['delivery']['delivery_time']) || empty($_SESSION['delivery']['delivery_time_h'])){
			include WOOSHOPCUSTOMIZER_DIR . '/includes/date-absent-popup-creator.php';
		}
		
	}
	
	
	/*
	 * get entries meta
	 * */
	static function formidable_get_entry_metas($field_id){
		global $frmdb, $wpdb;
		$records = $wpdb->get_col("select meta_value from $frmdb->entry_metas where field_id = '$field_id' order by meta_value asc");
		
		//var_dump($records);
		
		return array_unique($records);
	}
	
	
	/*
	 * This function add a section in form editng apge 
	 */
	static function frm_add_form_settings_section($sections,  $values){
		$sections['deliverycode'] = array(
			'class' => get_class(),
			'function' => 'deliverycode_settings'
		);
		
		return $sections;
	}
	
	
	//populates delivery code settings
	static function deliverycode_settings($values){		
		include WOOSHOPCUSTOMIZER_DIR . '/includes/delivery-code-settings.php';
	}
	
	//update the form new settings
	static function frm_form_options_before_update($options, $values){
			
		if(isset($values['options']['deliverycode']['enabled'])){			
			
			//update delivery address code form number
			update_option('delivery_addresscode_form', $values['id']);
			
			
			$options['deliverycode']['enabled'] = $values['options']['deliverycode']['enabled'];

			foreach(self::$form_keys as $key => $key_name){
				$options['deliverycode'][$key]['self'] = $values['options']['deliverycode'][$key]['self'];
				$options['deliverycode'][$key]['child'] = $values['options']['deliverycode'][$key]['child'];
			}
		}
			
		return $options;		
	}
	
	
	//get a select field
	static function get_select_field($key, $form_fields, $values){
		$str = "<select style='width: 160px;' name='options[deliverycode][$key][self]'><option value=''>Choose</option>";
		
		foreach($form_fields as $field){
			$str .= "<option ".selected($field->id, $values['deliverycode'][$key]['self'], false)." value='$field->id'>$field->name</options>";
		}
		
		$str .= '</select>';
		
		return $str;
	}
	
	
	//get input fields
	static function get_input_field($key, $form_fields, $values){
		$str = "<input type='text' name='options[deliverycode][$key][child]' value='" . $values['deliverycode'][$key]['child'] . "' />";
		return $str;
	}
	
	
	//get formdiable pro form id
	static function get_delivery_address_form_id(){
		return get_option('delivery_addresscode_form');
	}
	
	
	//process delivery code
	static function process_delivery_code(){
				
		if(empty($_SESSION['delivery']['delivery_time'])){
			$_SESSION['delivery']['delivery_time'] = date('Y-m-d', current_time('timestamp'));
			$_SESSION['delivery']['is_today'] = true;
		}
		
		if($_POST['delivery_code_applied'] == "Y") {
		
			if(empty($_POST['delivery_code']) && empty($_POST['delivery_city'])){
				return ;
			}
			else{
				foreach($_POST as $key => $value){
					$_SESSION['delivery'][$key] = $_POST[$key];
				}				 						
			}
			
		}
		
		if($_POST['delivery_code_applied_post'] == 'Y'){
			if(empty($_POST['delivery_code'])) return;			
			
			foreach($_POST as $key => $value){
				$_SESSION['delivery'][$key] = $_POST[$key];
			}
				
		}

		
		//from widget
		if($_POST['change_delivery_code_form'] == 'Y'){
			if(!empty($_POST['change_delivery_code'])){
				$_SESSION['delivery']['delivery_code'] = $_POST['change_delivery_code'];
			}
		}
		
		
		//now delviery date  is selected
		if(isset($_POST['delivery_time'])){
			if(empty($_POST['delivery_time']) || empty($_POST['delivery_time_h'])) return;
			$current_time = current_time('timestamp');
			$_SESSION['delivery']['delivery_time'] = $_POST['delivery_time'];
			$_SESSION['delivery']['is_today'] = date('Ymd', $current_time) == date('Ymd', strtotime($_POST['delivery_time']));
			$_SESSION['delivery']['delivery_time_h'] = $_POST['delivery_time_h'];
			
		}	
		
	}
	
	//empty cart if anything is chnaged from widget
	static function force_to_cart(){
				
		if(($_POST['change_delivery_code_form'] == 'Y') || ($_POST['change_delivery_date_form'] == 'Y') || ($_POST['delivery_code_applied_post'] == 'Y') || ($_POST['delivery_code_applied'] == 'Y')) {
			global $woocommerce;
			return $woocommerce->cart->empty_cart();
		}
		
	}
	
	/*
	 * get all orders based on date
	 * */
	static function get_orders($timestamp){
		global $wpdb;
		$d = date('Y-m-d', $timestamp);
		$sql = "select ID from $wpdb->posts where post_type like 'shop_order' and post_status like 'publish' and post_date like '%$d%'";
		
		return $wpdb->get_results($sql);
	}
	
	
	/*
	 * filtering the products based on distance criteria. It actually filter after every filtering hooks is done by wocommerce
	 * */
	static function woocommerce_product_query($q, $wc_q){
		
		if(empty($_SESSION['delivery'])) return;		
		
		$terms_ids = get_terms(self::food_provider_taxonomy, array('fields' => 'ids'));				
		$desired_terms = array();		
		$product_taxonomies = get_object_taxonomies( 'product' );
		
		
		if(in_array(self::food_provider_taxonomy, $product_taxonomies)) :
			if((strlen($_SESSION['delivery']['delivery_code']) > 0 && empty($_SESSION['delivery']['terms'])) || (isset($_POST['change_delivery_code']) && !empty($_POST['change_delivery_code']))){
				
			
				$shipping_address = self::get_street_address_from_delivery_code();
				
			//	var_dump($shipping_address);
			//	var_dump($_SESSION);
				
				//echo 'Customer Shippin Address: ' . $shipping_address . '<br/>';
				
			
				$GeoLocation = self::get_GeoLocation($shipping_address, null, 'mile');
				//var_dump($GeoLocation);
					
				foreach($terms_ids as $term_id){			
										
					$provider_address = array();
					
					$provider_address[] = get_field(self::$custom_fields['address_1'], self::food_provider_taxonomy . '_' . $term_id);
					$provider_address[] = get_field(self::$custom_fields['city'], self::food_provider_taxonomy . '_' . $term_id);
					$provider_address[] = get_field(self::$custom_fields['state'], self::food_provider_taxonomy . '_' . $term_id);
					//echo 'Restaurant Address: ' . implode(', ', $provider_address) . '<br/>';
					
					$GeoLocation->set_to(implode(', ', $provider_address));
					$distance = $GeoLocation->get_distance();
					
				//	var_dump($GeoLocation);
					
					//echo 'Restaurant Distance: ' . $distance . '<br/>';
								
					$maximum_delivery_distance = get_field(self::$custom_fields['max_delivery_distance'], self::food_provider_taxonomy . '_' . $term_id);			
					
					//echo 'Maximum Distance: ' . $maximum_delivery_distance . '<br/>';
									
					if($distance <= $maximum_delivery_distance){
						$desired_terms[] = $term_id;
					}
					
				}

				$_SESSION['delivery']['terms'] = $desired_terms;
			}
			elseif(strlen($_SESSION['delivery']['delivery_city']) > 0 && empty($_SESSION['delivery']['city_terms'])){
				foreach($terms_ids as $term_id){			
					$provider_city = get_field(self::$custom_fields['city'], self::food_provider_taxonomy . '_' . $term_id);
					
					if(strcasecmp($_SESSION['delivery']['delivery_city'], $provider_city) == 0){
						$desired_terms[] = $term_id;
					}						
				}
				
				$_SESSION['delivery']['city_terms'] = $desired_terms;
			}
			
	//		var_dump($_SESSION['delivery']);
	//		var_dump($_SESSION['delivery']['terms']);
			
			//if the terms are empty we skip the step
			if(isset($_SESSION['delivery']['terms'])){
				$q->set('tax_query', array(
					array(
						'taxonomy' => self::food_provider_taxonomy,
						'field' => 'id',
						'terms' => $_SESSION['delivery']['terms']
					)
				));			
				
			}
			elseif(isset($_SESSION['delivery']['city_terms'])){
				$q->set('tax_query', array(
					array(
						'taxonomy' => self::food_provider_taxonomy,
						'field' => 'id',
						'terms' => $_SESSION['delivery']['city_terms']
					)
				));
			}
							
		endif;		
		
		
	}
	
	
	//distance determination object
	static function get_GeoLocation($from, $to, $unit){
		if(!class_exists('GeoLocation')){
			include WOOSHOPCUSTOMIZER_DIR . '/classes/class.google-maps.php';
		}
		
		return new GeoLocation($from, $to, $unit);
	}
	
	
	
	/**
	 * calls when date is changed from widget
	 * return an htmt
	 * */
	static function superstore_date_changed(){
		$date = $_POST['date'];
		$now = current_time('timestamp');
		$delivery_times = WooDeliveryTimeWidget::get_times();
		
		$is_today = $date == date('Y-m-d', $now);
		
		echo '<option value="">Time</option>';
		
		if($delivery_times){
			foreach($delivery_times as $t){
				$generated_time_stamp = strtotime($date . ' ' . $t) - 1*60*60;	
				//echo $deadline . ' ' . $generated_time_stamp ; die();
				
				if($is_today){
					if($now < $generated_time_stamp){
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
		
		exit;		
	}
	
}
