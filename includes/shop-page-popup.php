<?php 
	global $frm_form;
		
	$delivery_form_id = self::get_delivery_address_form_id();		
	$form = $frm_form->getOne($delivery_form_id);
	$city_field_id = $form->options['deliverycode']['city'];
	$delivery_code_field_id = $form->options['deliverycode']['code']['self'];
	
	if($city_field_id > 0){
		$cities = self::formidable_get_entry_metas($city_field_id['child']);
	}	
	
	
?>

<div id="myModal" class="reveal-modal">
	<form action="" method="post">
		
		<input type="hidden" name="delivery_form_id" value="<?php echo $delivery_form_id; ?>">
		<input type="hidden" name="delivery_code_field_id" value="<?php echo $delivery_code_field_id; ?>" />
		
		<input type="hidden" name="delivery_code_applied" value="Y" />
		
		<h1>Deliver Code</h1>
		<p>Please Put in Your Delivery Code</p>
		<p> <input type="text" name="delivery_code" value="" /></p>
		<p> Don't Know Your Delivery Code? <a href="#"> Create one </a></p>
		<p> Or choose Your City 
			
			<?php 
				
			//	var_dump($cities);
			//	var_dump($city_field_id);
				
				
				//$a = FrmProFieldsHelper::get_field_options($delivery_form_id);
				
				//var_dump($delivery_form_id);
				//var_dump($a);
			
			?>
			
			<select style="min-width: 160px" name="delivery_city">
				<option value="">Choose</option>
				
				<?php 
					
				
					if(is_array($cities)){
						foreach($cities as $city){
							echo "<option value='$city'>$city</option>";
						}								
					}
				?>
				
			</select> 
		</p>
		
		<?php		
			
			//var_dump($_SESSION);
			
		?>
		
		<p><input type="submit" value="Proceed" /></p>
		
	</form>
	<a class="close-reveal-modal">&#215;</a>
</div>