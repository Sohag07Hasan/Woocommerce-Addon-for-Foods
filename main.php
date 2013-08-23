<?php
/*
 * Plugin name: Woocommerce Addon to meet admin choices
 * author: Mahibul Hasan
 * */

define("WOOSHOPCUSTOMIZER_FILE", __FILE__);
define("WOOSHOPCUSTOMIZER_DIR", dirname(__FILE__));
define("WOOSHOPCUSTOMIZER_URL", plugins_url('', __FILE__));

session_start();

/*
//database manipulation
include WOOSHOPCUSTOMIZER_DIR . '/classes/class.db.php';
global $WooShopDb;
$WooShopDb = new WooShopCustomizerDB();
*/

include WOOSHOPCUSTOMIZER_DIR . '/classes/class.wooshop-customizer-A.php';
WooShopCustomizerA::init();


include WOOSHOPCUSTOMIZER_DIR . '/classes/customizer-B.php';
WooShopCustomizerB::init();

//widgets
include WOOSHOPCUSTOMIZER_DIR . '/classes/widget-delivery-date.php';
include WOOSHOPCUSTOMIZER_DIR . '/classes/widget-delivery-code.php';
