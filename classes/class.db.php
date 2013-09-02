<?php
/**
 * This class will handle the order meta table
 * */
class WooShopCustomizerDB{
	
	private $control_table;
	private $db;
	
	/*constructor*/
	function __construct(){
		global $wpdb;
		$this->control_table = $wpdb->prefix . 'order_controller';
		$this->db = $wpdb;
	}
	
	
	/**
	 * synchronize database
	 * creates table if not exists while activating
	 * */
	function sync_db(){
		$sql = array();
		
		$sql[] = "create table if not exists $this->control_table(
			ID bigint not null auto_increment primary key,
			order_id bigint not null unique,
			order_data text not null
		)";
		
		foreach($sql as $s){
			$this->db->query($s);
		}
	}
	
	
	
	/**
	 * save order meta
	 * @order_id unique id when an oder is created
	 * @meta_data brands specific data to be saved for further use
	 * */
	function save_order_meta($order_id, $meta_data){
		$this->db->insert($this->control_table, array('order_id' => (int) $order_id, 'order_data' => maybe_serialize($meta_data)), array('%d', '%s'));
	}
	
	
	/**
	 * return the order meta
	 * */
	
	
	
}