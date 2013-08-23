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
	
	//save the brand details
	static $brand_details = array();
	
	//termporary product info saving
	static $temp_product_info = array();
	
	//preshoppage
	static $pre_shop_products = array();
	
	
	/*
	 * constructor that holds the hooks and filters 
	 * */
	static function init(){
		//savign meta data while an order is created to trace it later
		add_action('woocommerce_checkout_order_processed', array(get_class(), 'new_order_just_processed'), 10, 2);
		
		//categorize products like sale, sold out, deal on
		add_action('highlycustomized_before_shop_loop', array(get_class(), 'catgorize_products_for_shop_page'), 100);
		
		//chancing the flash according to the conditions
	//	add_filter('woocommerce_sale_flash', array(get_class(), 'show_appropriate_flash'), 100, 3);
		
		
		//remove the add to cart button
		add_filter('woocommerce_loop_product_buy_able', array(get_class(), 'loop_product_buy_able'), 10, 2);
		
		
		//hook to single product page
		add_action('woocommerce_before_single_product', array(get_class(), 'categorize_brands_for_single_products'));
				
		
		///custom hooks to categorize the proudcts in preshop page
		add_action('highlycustomized_categorize_products', array(get_class(), 'highlycustomized_categorize_products'));
		
		
		//after every preshop
		add_action('after_every_pre_shop', array(get_class(), 'add_more_button'), 10, 2); 
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
	}
	
	
	/**
	 * Sale flash, add to cart, new custom messages are being generated
	 * */
	static function categorize_brands_for_single_products(){
		global $product, $post;
		if(empty($_SESSION['delivery']['delivery_code']) || empty($_SESSION['delivery']['delivery_time'])) return;
				
		$order_query = self::get_orders(array('date', 'time', 'code'));
				
		$order_items = array();
		$order_brands = array();
		
		$brand_details = array();
		
	//query quantity  against brand id
		if($order_query){
			//delivery code count
			$delivery_code_count = $order_count = $order_query->post_count;
			$order_ids = $order_query->posts;
			
			if($order_ids){
				foreach($order_ids as $order_id){
					$order = new WC_Order($order_id);
					$items = $order->get_items();
					
					//var_dump($items); return;
					
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
				}
			}
		}
		
		var_dump($order_brands);
		
		//checking against this specific product
		$brand_ids = wp_get_object_terms($product->id, self::food_provider_taxonomy, array('fields'=>'ids'));
		if($brand_ids){
			foreach($brand_ids as $b_id){
				if(isset($order_brands[$b_id])){
					$custom_fields = self::get_terms_custom_fields($b_id);
					extract($custom_fields);

					$condition = array();
					
					$condition[1] = true; //already filtered the products
					$condition[2] = $details['price'] >= $min_order;
					$condition[3] = $delivery_code_count < 30 / $avg_delivery_speed * $max_delivery_crew;
					
					//identifying today and tomorrow
					if($_SESSION['delivery']['is_today']){
						$condition[4] = $details['qty'] < $max_prod_rate_per_rush_hour;
					}
					else{
						$condition[4] = $details['qty'] < $max_prod_rate_per_non_rush_hour;
					}
					
					//var_dump($condition);				
					//arragne brands with appropriate texts
					if($condition[1] && $condition[2]){
						//$brand_details[$b_id]['icon'] = 'Deal On';
						if($condition[3]){
							if($condition[4]){
								self::$brand_details[$b_id]['icon'] = 'Deal On';
								self::$brand_details[$b_id]['buyable'] = true;
							}
							else{
								self::$brand_details[$b_id]['buyable'] = false;
								self::$brand_details[$b_id]['icon'] = 'Sold Out';
								self::$brand_details[$b_id]['message'] = 'Max # of meals for this delivery time has been reached. We only take ' . $max_prod_rate_per_rush_hour . ' Max meals per hour.';
							}
						}
						else{
							self::$brand_details[$b_id]['icon'] = 'Not On';
							self::$brand_details[$b_id]['buyable'] = false;
							self::$brand_details[$b_id]['message'] = 'Max delivery places reached. We only deliver to ' . 30 / $avg_delivery_speed * $max_delivery_crew . ' different places Max within half an hours';
						}
					}
					else{
						self::$brand_details[$b_id]['buyable'] = true;
						self::$brand_details[$b_id]['icon'] = 'Not On';
					}				
				}
			}
		}
		
		var_dump(self::$brand_details);
	}
		
	
	
	/*
	 *Identify the brnds/restaurants to show exat brand logos
	 *sold out, deal on 
	 */
	static function catgorize_products_for_shop_page(){
		//ingnoring if delivery code and date are empty
		if(empty($_SESSION['delivery']['delivery_code']) || empty($_SESSION['delivery']['delivery_time'])) return;

		//default conditions
		$condition_1 = true;
		$condition_2 = true;
		$condition_3 = true;
		
		
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
					$order = new WC_Order($order_id);
					$items = $order->get_items();
					
					//var_dump($items); return;
					
					if($items){
						foreach($items as $item){
							
							$brands = wp_get_object_terms($item['product_id'], self::food_provider_taxonomy, array('fields'=>'ids'));
							if($brands){
								foreach($brands as $b){
									$order_brands_dtc[$b]['qty'] += $item['qty'];
									$order_brands_dtc[$b]['price'] += $item['line_subtotal'];
								}
							}
							
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
					$items = $order->get_items();
					
					$code = get_post_meta($order_id, self::delivery_code, true);
				//	var_dump($code);
					
					if($items){
						foreach($items as $item){
							$brands = wp_get_object_terms($item['product_id'], self::food_provider_taxonomy, array('fields'=>'ids'));
							if($brands){
								foreach($brands as $b){
									$order_brands_dt[$b]['orders'][] = $order_id;
									$order_brands_dt[$b]['items'][] = $item['product_id'];
									$order_brands_dt[$b]['codes'];
									
									//delivery codes for orders with price
									$delivery_codes['price'][$code] += $item['line_subtotal'];
									
								}
							}
						}
					}
				}
			}
		}
		
		
	//	var_dump($order_brands_dtc);
	//	var_dump($order_brands_dt);
	//	var_dump($delivery_codes);
	
		if(count($delivery_codes['prices'])){
			arsort($delivery_codes['price']);
			
			$codes = array_keys($delivery_codes['price']);
			$prices = array_values($delivery_codes['price']);
		}
		
	//	var_dump($codes);
	//	var_dump($prices);
		
		if($order_brands_dt){
			foreach($order_brands_dt as $b_id => $details){
				$custom_fields = self::get_terms_custom_fields($b_id);
				extract($custom_fields);

				//var_dump($custom_fields);
				
				//condition 1 checking
				if(isset($order_brands_dtc[$b_id])){
					if($order_brands_dtc[$b_id]['price'] < $min_order){
						$condition_1 = true;
					}
				}
				
				$codes = array_slice($codes, 0, $max_delivery_distance);
				$prices = array_slice($prices, 0, $max_delivery_distance);
				

				//var_dump($codes);
				//var_dump($prices);
				
				if(in_array($_SESSION['delivery']['delivery_code'], $codes)){
					$condition_1 = true;
				}
				else{
					if(isset($prices[$max_delivery_distance -1]) && $prices[$max_delivery_distance -1] < $min_order){
						$condition_1 = true;
					}
					else{
						self::$brand_details['others'][] = $b_id;
						continue;
					}
				}
				
				/*
				//delivery address code condition transistios
				if(count($delivery_codes['price']) > $max_delivery_distance){
					$counter = 0;
					foreach($delivery_codes['price'] as $code => $price){
						if($counter == $max_delivery_distance) break;
						
						if($price > $min_order && $code == $_SESSION['delivery']['delivery_code']){
							
						}
						
						$counter ++;
					}
				}
				*/
						
				//condition 2
				$orders = array_unique($details['orders']); //remove dupliated entries
				if(count($orders) < (30 / $avg_delivery_speed * $max_delivery_crew)){
					$condition_2 = true;
				}
				
				//condition 3
				$items = array_unique($details['items']); //remving duplicate entries
				$max_production_rate = $_SESSION['is_today'] ? $max_prod_rate_per_rush_hour : $max_prod_rate_per_non_rush_hour;
				if(count($items) < $max_production_rate){
					$condition_3 = true;
				}				
				
				
				//condition 1 transition
				//$max_delivery_distance
				
				
				if($condition_1){
					if($condition_2){
						if($condition_3){
							self::$brand_details['deal_on'][] =$b_id;
						}
						else{
							self::$brand_details['sold_out'][] = $b_id;
						}
					}
					else{
						self::$brand_details['sold_out'][] = $b_id;
					}
				}
				else{
					self::$brand_details['unlock_it'][] =$b_id;
				}
			}
		}
		/*
		
		var_dump($condition_1);
		var_dump($condition_2);
		var_dump($condition_3);
		
		var_dump(self::$brand_details);	
		*/
	}
	
	
	/**
	 * Deal on, sold out etc flash is used checking the conditionals
	 */
	static function show_appropriate_flash($string, $post, $product){
		$flash = '<span class="onsale">%s!</span>';
		
		if(!empty($_SESSION['delivery']['delivery_code']) && !empty($_SESSION['delivery']['delivery_time'])){		
			$string = '<span class="onsale">Deal On!</span>';
		}		
		
		$brand_ids = wp_get_object_terms($product->id, self::food_provider_taxonomy, array('fields'=>'ids'));
		foreach($brand_ids as $b_id){
			if(isset(self::$brand_details[$b_id])){
				self::$temp_product_info[$product->id]['info'] = self::$brand_details[$b_id];
				$string = sprintf($flash, self::$brand_details[$b_id]['icon']);
				
				return $string;
			}
		}
		
		
		
		return $string;
	}
	
	
	
	/**
	 * Categorize the products for pre shop page
	 * */
	static function highlycustomized_categorize_products($post){
		$brand_ids = wp_get_object_terms($post->ID, self::food_provider_taxonomy, array('fields'=>'ids'));
		
		if($brand_ids && self::$brand_details){
			foreach($brand_ids as $b_id){
				foreach(self::$brand_details as $key => $brands){
					if(in_array($b_id, $brands)){
						self::$pre_shop_products[$key][] = $post;
						break;
					}
				}
			}
		}
		else{
			self::$pre_shop_products['deal_on'][] = $post;
		}
	}
	
	
	/**
	 * Remove the add to cart button checking the conditionals
	 * try to use show_appropriate_flash method first, if not found, it will make it's own query
	 * */
	static function loop_product_buy_able($status, $product){
		if(isset(self::$temp_product_info[$product->id])){
			
		//	var_dump(self::$temp_product_info[$product->id]);
			
			return self::$temp_product_info[$product->id]['info']['buyable'];
		}
		else{
			$brand_ids = wp_get_object_terms($product->id, self::food_provider_taxonomy, array('fields'=>'ids'));
			foreach($brand_ids as $b_id){
				if(isset(self::$brand_details[$b_id])){
					self::$temp_product_info[$product->id]['info'] = self::$brand_details[$b_id];
					return self::$temp_product_info[$product->id]['info']['buyable'];
				}
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
			'max_delivery_distance' => get_field(WooShopCustomizerA::$custom_fields['max_delivery_distance'], self::food_provider_taxonomy . '_' . $term_id)		
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
						'compare' => 'LIKE'
					);
					break;
				case 'time':
					$order_args['meta_query'][] = array(
						'key' => self::delivery_hour,
						'value' => $_SESSION['delivery']['delivery_time_h'],
						'compare' => 'LIKE'
					);
					break;
				case 'code':
					$order_args['meta_query'][] = array(
						'key' => self::delivery_code,
						'value' => $_SESSION['delivery']['delivery_code'],
						'compare' => 'LIKE'
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
}