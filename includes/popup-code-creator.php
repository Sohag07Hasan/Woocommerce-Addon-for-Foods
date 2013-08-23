<?php 
	global $frm_form;
		
	$delivery_form_id = self::get_delivery_address_form_id();		
	$form = $frm_form->getOne($delivery_form_id);
	$city_field_id = $form->options['deliverycode']['city'];
	$delivery_code_field_id = $form->options['deliverycode']['code']['self'];
		
?>

<div id="PostModal" class="reveal-modal">
	<form action="" method="post">
		
		<input type="hidden" name="delivery_form_id" value="<?php echo $delivery_form_id; ?>">
		<input type="hidden" name="delivery_code_field_id" value="<?php echo $delivery_code_field_id; ?>" />
		
		<input type="hidden" name="delivery_code_applied_post" value="Y" />
		
		
		<p>Please Put in Your Delivery Code</p>
		<p> <input type="text" name="delivery_code" value="" /></p>
		<p> Don't Know Your Delivery Code? <a href="#"> Create one </a></p>
		
		<input type="hidden" name="delivery_city" value="<?php echo $_SESSION['delivery']['delivery_city']; ?>" />
				
		<p><input type="submit" value="Proceed" /></p>
		
	</form>
	<a class="close-reveal-modal">&#215;</a>
</div>