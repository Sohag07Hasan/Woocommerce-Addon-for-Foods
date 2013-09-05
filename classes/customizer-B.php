<?php
/*
 * 1. Tracing orders by specific date and time
 * 2. implentation of shop page logics
 * 3. filter out the loop flash image with the page logic results
 * */


class WooShopCustomizerB{
	
	const delivery_date = "_delivery_time";
	const delivery_hour = "_delivery_time_h";
	const food_provider_taxonomy = "brands";
	const delivery_code = "_delivery_code";
	const brand_info = "_brand_specific_info";
	
	//save the brand details
	static $brand_details = array();
	
	//termporary product info saving
	static $temp_product_info = array();
	
	//preshoppage
	static $pre_shop_products = array();
	
	static $pre_shop_product_ids = array();
	
	static $product_status = array();
	
	
	/*
	 * constructor that holds the hooks and filters 
	 * */
	static function init(){
		//savign meta data while an order is created to trace it later
		add_action('woocommerce_checkout_order_processed', array(get_class(), 'new_order_just_processed'), 10, 2);
		
		//categorize products like sale, sold out, deal on		
		add_action('highlycustomized_before_shop_loop', array(get_class(), 'catgorize_products_for_pre_shop_page'), 100);
		add_action('highlycustomized_categorize_products', array(get_class(), 'highlycustomized_categorize_products'));
		
		//single product and preshop algorithm is similar
		add_action('woocommerce_before_single_product', array(get_class(), 'catgorize_products_for_pre_shop_page'));
		add_action('highlycustomized_single_product_status', array(get_class(), 'highlycustomized_single_product_status'));
				
		//remove the add to cart button from preshop page
		add_filter('preshop_woocommerce_loop_product_buy_able', array(get_class(), 'preshop_loop_product_buy_able'), 10, 2);
		
		add_filter('woocommerce_loop_product_buy_able', array(get_class(), 'loop_product_buy_able'), 10, 2);
		
		//single product
		//add_filter('woocommerce_is_purchasable', array(get_class(), 'woocommerce_is_purchasable'), 10, 2);
		
		//chancing the flash according to the conditions
		//add_filter('woocommerce_sale_flash', array(get_class(), 'show_appropriate_flash'), 100, 3);
				
		
		
		
		//after every preshop
		add_action('after_every_pre_shop', array(get_class(), 'add_more_button'), 10, 2); 
		
		
		//oder details page
		add_filter('brands_information', array(get_class(), 'brands_information_for_order_details'));
		
		
		//preshop add to cart button
		add_action('preshop_woocommerce_after_shop_loop_item', array(get_class(), 'preshop_woocommerce_after_shop_loop_item'));
		
	}
	
	
	/**
	 * Order detais page
	 * */
	static function brands_information_for_order_details($order){
		$brandinfo = get_post_meta($order->id, self::brand_info, true);
		$brands = array();
		
		if($brandinfo){
			foreach($brandinfo as $b_id => $b_info){
				$term = get_term($b_id, WooShopCustomizerA::food_provider_taxonomy);
				$custom_fields = self::get_terms_custom_fields($b_id);
				extract($custom_fields);
				
				$brands[] = array(
					'id' => $b_id,
					'name' => $term->name,
					'phone' => $store_phone	
				);
			}
		}
		
		return $brands;
	}
	
	/**
	 * shipping address using delivery codes
	 * */
	static function get_shipping_address($order){
		global $frm_form, $wpdb,$frmdb;
		
		$delivery_form_id = WooShopCustomizerA::get_delivery_address_form_id();
		$form = $frm_form->getOne($delivery_form_id);
		$delivery_code_field_id = $form->options['deliverycode']['code']['self'];
		
		$delivery_code = get_post_meta($order->id, self::delivery_code, true);
		$item_values = WooShopCustomizerA::get_item_metas_by_item_id($delivery_code, $delivery_code_field_id);
		
		$address = array();
				
		
		foreach(WooShopCustomizerA::$shipping_keys as $key => $shipping_key){
			
			
			$item_id = $item_values[$form->options['deliverycode'][$key]['self']];			
			$field_id = $form->options['deliverycode'][$key]['child'];
							
			if(in_array($key, array('state', 'city'))){					
				WooShopCustomizerA::$shipping_default_values[$shipping_key] = $wpdb->get_var("select meta_value from $frmdb->entry_metas where field_id = '$field_id' and item_id = '$item_id'");
			}
			else{
				WooShopCustomizerA::$shipping_default_values[$shipping_key] = $item_values[$form->options['deliverycode'][$key]['self']];
			}
						
		}
		
		return WooShopCustomizerA::$shipping_default_values;
		
	}
	
	
		
	/*
	 * Saves delivery date and time to with created order
	 * 
	 */
	static function new_order_just_processed($order_id, $order_data){
		if(isset($_SESSION['delivery']['delivery_time'])){
			update_post_meta($order_id, self::delivery_date, $_SESSION['delivery']['delivery_time']);
		}
		
		if(isset($_SESSION['delivery']['delivery_time_h'])){
			update_post_meta($order_id, self::delivery_hour, $_SESSION['delivery']['delivery_time_h']);
		}
		
		if(isset($_SESSION['delivery']['delivery_code'])){
			update_post_meta($order_id, self::delivery_code, $_SESSION['delivery']['delivery_code']);
		}
		
		//saving date to the extra table
		
		$order = new WC_Order($order_id);
		//var_dump($order->get_order_total());
		$items = $order->get_items();
		
		$order_brands = array();
		
		if($items){
			foreach($items as $item){
				
				$brands = wp_get_object_terms($item['product_id'], self::food_provider_taxonomy, array('fields'=>'ids'));
				if($brands){
					foreach($brands as $b){
						$order_brands[$b]['qty'] += (int) $item['qty'];
						$order_brands[$b]['price'] += $item['line_subtotal'];
					}
				}							
			}
		}

		update_post_meta($order_id, self::brand_info, $order_brands);
	}
	
		
	
	/**
	 * single product status
	 * **/
	static function highlycustomized_single_product_status($post){
		$brand_ids = wp_get_object_terms($post->ID, self::food_provider_taxonomy, array('fields'=>'ids'));
		
		if($brand_ids && self::$brand_details){
			foreach($brand_ids as $b_id){
				foreach(self::$brand_details as $key => $brands){
					if(in_array($b_id, $brands)){
						self::$product_status['type'] = $key;
						break;
					}
				}
			}
		}
		
		//var_dump(self::$brand_details);
		//var_dump(self::$product_status);
		
		if(!in_array(self::$product_status['type'], array('unlock_it'))){
			remove_all_actions('woocommerce_simple_add_to_cart');
			remove_all_actions('woocommerce_grouped_add_to_cart');
			remove_all_actions('woocommerce_variable_add_to_cart');
			remove_all_actions('woocommerce_external_add_to_cart');
		}

		
		
	}
	
	
	/**
	 * product is purchaseable
	 * */
	static function woocommerce_is_purchasable($status, $product){
		
		if(!$status) return $status;
		
		if(self::$product_status['type'] == 'deal_on') return true;
		if(self::$product_status['type'] == 'unlock_it') return true;
		
		return false;
	}
	
	
	/**
	 * categories products for preshop page
	 * */
	static function catgorize_products_for_pre_shop_page(){
		//ingnoring if delivery code and date are empty
		if(empty($_SESSION['delivery']['delivery_code']) || empty($_SESSION['delivery']['delivery_time']) || empty($_SESSION['delivery']['delivery_time_h'])) return;
		
		//default conditions
		$condition_1 = false;
		$condition_2 = true;
		$condition_3 = true;
		$auxiliary_1 = false;
		$auxiliary_2 = false;
		
		
		$order_query_dtc = self::get_orders(array('date', 'time', 'code'));
		$order_query_dt = self::get_orders(array('date', 'time'));
		
		//var_dump($order_query_dtc->posts);
		//var_dump($order_query_dt->posts);
		
		$order_items = array();
		$order_brands_dtc = array();
		$order_brands_dt = array();
		$brand_details = array();
		$delivery_codes = array();
		
		$codes = array();
		$prices = array();
		
		/**
		 * Condition 01
		 * Orders are considered for date, time and code
		* */
		if($order_query_dtc){
			//delivery code count
			$delivery_code_count = $order_count = $order_query_dtc->post_count;
			$order_ids_dtc = $order_query_dtc->posts;
		
				
			if($order_ids_dtc){
				foreach($order_ids_dtc as $order_id){
									
					$brandinfo = get_post_meta($order_id, self::brand_info, true);
					if($brandinfo){
						foreach($brandinfo as $b_id => $b_info){
						//	$order_brands_dtc[$b_id]['qty'] += $b_info['qty'];
							$order_brands_dtc[$b_id]['price'] += $b_info['price'];
						}
					}
				}				
			}
						
		}
			
		//condition 2 manipulation
		if($order_query_dt){
			$order_ids_dt = $order_query_dt->posts;
			
			if($order_ids_dt){
				foreach($order_ids_dt as $order_id){
					$order = new WC_Order($order_id);
					
				//	$items = $order->get_items();
						
					$code = get_post_meta($order_id, self::delivery_code, true);
					$delivery_codes[$code] += $order->get_order_total();
					
					$brandinfo = get_post_meta($order_id, self::brand_info, true);
					
					if($brandinfo){
						foreach($brandinfo as $b_id => $b_info){
							$order_brands_dt[$b_id]['orders'][] = $order_id;
							$order_brands_dt[$b_id]['qty'] += $b_info['qty'];
							//$order_brands_dt[$b_id]['price'] += $b_info['price'];
							$order_brands_dt[$b_id]['codes'][] = $code;
						}
					}					
						
					
				}
			}
		}
		
		/*
		var_dump($order_ids_dtc);
		echo '<br/>';
		var_dump($order_brands_dtc);
		echo '<br/>';
		var_dump($order_brands_dt);
		echo '<br/>';
		var_dump($delivery_codes);
		echo '<br/>';
		*/
		//var_dump($order_brands_dt);
		
		if(count($delivery_codes)){
			arsort($delivery_codes);				
			$codes = array_keys($delivery_codes);
			$prices = array_values($delivery_codes);
		}
				
		if($order_brands_dt){
			foreach($order_brands_dt as $b_id => $details){
				$custom_fields = self::get_terms_custom_fields($b_id);
				extract($custom_fields);
		
				//var_dump($custom_fields);
		
				//condition 1 checking
				if(isset($order_brands_dtc[$b_id])){
					if($order_brands_dtc[$b_id]['price'] >= $min_order){
						$condition_1 = true;
					}
				} 
				//var_dump($codes); echo "<br/>";
				//var_dump($max_delivery_distance); echo "<br/>";
				
				if(count($codes) >= (int) $max_delivery_distance){
					$codes = array_slice($codes, 0, $max_delivery_distance);
					$prices = array_slice($prices, 0, $max_delivery_distance);
					
				//	var_dump($codes); echo "<br/>";
				//	var_dump($prices); echo "<br/>";
				//	var_dump($max_delivery_distance); echo "<br/>";
					
					
					if($prices[$max_delivery_distance -1] >= $min_order){
						if(in_array($_SESSION['delivery']['delivery_code'], $codes)){
							$condition_1 = true;
							$auxiliary_1 = true;
						}						
					}
					else{
						if(in_array($_SESSION['delivery']['delivery_code'], $codes) && $delivery_codes[$_SESSION['delivery']['delivery_code']] >= $min_order){
							$auxiliary_2 = false;
						}
						else{
							$auxiliary_2 = true;
						}
					}							
				}
				
				/*
				//condition 2 (earlier it was based on orders)
				$orders = array_unique($details['orders']); //remove dupliated entries
				if(count($orders) < (30 / $avg_delivery_speed * $max_delivery_crew)){
					$condition_2 = true;
				}
				*/
				
				//condition2 now based on codes (latest update)
				//$used_codes = array_unique($details['codes']);
				$used_codes = $codes;
				//var_dump($used_codes); echo "<br/>";
				//var_dump($avg_delivery_speed);
				//var_dump($max_delivery_crew);
				
				if(count($used_codes) >= (30 / $avg_delivery_speed * $max_delivery_crew)){
					if(!in_array($_SESSION['delivery']['delivery_code'], $used_codes)){
						$condition_2 = false;
					}					
				}
		
				//condition 3				
				$max_production_rate = $_SESSION['is_today'] ? $max_prod_rate_per_rush_hour : $max_prod_rate_per_non_rush_hour;
				if($details['qty'] > $max_production_rate){
					$condition_3 = false;
				}
		
				
				//var_dump($condition_1); echo "<br/>";
				//var_dump($condition_2); echo "<br/>";
				//var_dump($condition_3); echo "<br/>";
				//var_dump($auxiliary_1); echo "<br/>";
				
				
				//latest lgoic
				if($condition_1){
					if($auxiliary_1){
						self::$brand_details['deal_on'][] = $b_id;
					}
					else{
						if($condition_2){
							if($condition_3){
								self::$brand_details['deal_on'][] = $b_id;							
							}
							else{
								self::$brand_details['sold_out'][] = $b_id;							
							}
						}
						else{
							self::$brand_details['others'][] = $b_id;
						}
					}					
				}
				else{
					
					if($auxiliary_2){
						self::$brand_details['unlock_it'][] = $b_id;
					}
					else{
						self::$brand_details['others'][] = $b_id;
					}
					
					//self::$brand_details['unlock_it'][] = $b_id;
					
					/*									
					if($condition_2){
						self::$brand_details['unlock_it'][] = $b_id;
					}
					else{
						self::$brand_details['others'][] = $b_id;
					}
					* */
					 
				}
				
			}
		}
		
		//var_dump(self::$brand_details);
	}
	
	/**
	 * Deal on, sold out etc flash is used checking the conditionals
	 */
	static function show_appropriate_flash($string, $post, $product){		
		return $string;
	}
	
	
	
	/**
	 * Categorize the products for pre shop page
	 * */
	static function highlycustomized_categorize_products($post){
		
		//var_dump($post);
		$status = false;
		
		$brand_ids = wp_get_object_terms($post->ID, self::food_provider_taxonomy, array('fields'=>'ids'));
		
		if($brand_ids && self::$brand_details){
			foreach($brand_ids as $b_id){
				foreach(self::$brand_details as $key => $brands){
					if(in_array($b_id, $brands)){
						self::$pre_shop_products[$key][] = $post;
						self::$pre_shop_product_ids[$key][] = $post->ID;
						$status = true;
						break;
					}
				}
			}
		}
		
		if(!$status){
			self::$pre_shop_products['unlock_it'][] = $post;
			self::$pre_shop_product_ids['unlock_it'][] = $post->ID;
		}
		
	}
	
	
	/**
	 * Remove the add to cart button checking the conditionals
	 * try to use show_appropriate_flash method first, if not found, it will make it's own query
	 * */
	static function preshop_loop_product_buy_able($status, $product){
		if(is_array(self::$pre_shop_product_ids['deal_on']) && in_array($product->id, self::$pre_shop_product_ids['deal_on'])) return false;
		if(is_array(self::$pre_shop_product_ids['sold_out']) && in_array($product->id, self::$pre_shop_product_ids['sold_out'])) return false;
		if(is_array(self::$pre_shop_product_ids['unlock_it']) && in_array($product->id, self::$pre_shop_product_ids['unlock_it'])) return true;
		if(is_array(self::$pre_shop_product_ids['others']) && in_array($product->id, self::$pre_shop_product_ids['others'])) return false;
		
		return $status;
	}
	
	
	//shop page add to cart button remove button
	static function loop_product_buy_able($status, $product){
		if(isset($_REQUEST['shop_type'])){
			if(in_array($_REQUEST['shop_type'], array('deal_on', 'others', 'sold_out'))){
				$status = false;
			}
		}
		
		return $status;
	}
	
	
	/*
	 * get the custom fields of a term id set by advanced custom fields
	 * */
	static function get_terms_custom_fields($term_id){
		return array(
			'max_prod_rate_per_rush_hour' => get_field(WooShopCustomizerA::$custom_fields['max_prod_rate_per_rush_hour'], self::food_provider_taxonomy . '_' . $term_id),
			'avg_delivery_speed' => get_field(WooShopCustomizerA::$custom_fields['avg_delivery_speed'], self::food_provider_taxonomy . '_' . $term_id),
			'max_delivery_crew' => get_field(WooShopCustomizerA::$custom_fields['max_delivery_crew'], self::food_provider_taxonomy . '_' . $term_id),
			'min_order' => get_field(WooShopCustomizerA::$custom_fields['min_order'], self::food_provider_taxonomy . '_' . $term_id),
			'max_prod_rate_per_non_rush_hour' => get_field(WooShopCustomizerA::$custom_fields['min_order'], self::food_provider_taxonomy . '_' . $term_id),
			'max_delivery_distance' => get_field(WooShopCustomizerA::$custom_fields['max_delivery_distance'], self::food_provider_taxonomy . '_' . $term_id),		
			'store_phone' => get_field(WooShopCustomizerA::$custom_fields['store_phone'], self::food_provider_taxonomy . '_' . $term_id)
		);
	}
		
	
	/*
	 * Get orders based on delivery time
	 * */
	static function get_orders($args = array()){
				
		$order_args = array(
			'post_type' => 'shop_order',
			'meta_query' => array(
				'relation' => 'AND',
			),
			'fields' => 'ids'
		);
		
		
		
		foreach($args as $arg){
			switch ($arg){
				case 'date':
					$order_args['meta_query'][] = array(
						'key' => self::delivery_date,
						'value' => $_SESSION['delivery']['delivery_time'],
						'compare' => '='
					);
					break;
				case 'time':
					$order_args['meta_query'][] = array(
						'key' => self::delivery_hour,
						'value' => $_SESSION['delivery']['delivery_time_h'],
						'compare' => '='
					);
					break;
				case 'code':
					$order_args['meta_query'][] = array(
						'key' => self::delivery_code,
						'value' => $_SESSION['delivery']['delivery_code'],
						'compare' => '='
					);
			}
		}	
			
		
		$query = new WP_Query( $order_args );
				
		wp_reset_query();
		
		return $query;
	}

	
	/**
	 * add more button for every preshop type
	 * */
	static function add_more_button($type, $shop){
		
		$url = curPageURL();
		$shop_url = add_query_arg(array('shop_type' => $type), $url);
		
		?>
		
		<li class="moreproducts">
			<div class="categories">&nbsp;</div>
			<a href="<?php echo $shop_url; ?>">More</a>
		</li>
		
		<?php
	}
	
	
	
	/**
	 * pre shop conditional add to cart button
	 * */
	static function preshop_woocommerce_after_shop_loop_item(){
		woocommerce_get_template( 'loop/pre-shop-add-to-cart.php' );
	}
}
