<?php 
	$form_fields = FrmFieldsHelper::get_form_fields($values['id']);
?>

<div style="display: none;" class="deliverycode_settings tabs-panel">
	<table class="form-table">
		<tr>
			<td><input type="checkbox" name="options[deliverycode][enabled]" id="deliverycode_enabled" value="1" <?php checked($values['deliverycode']['enabled'], 1); ?> /></td>
       		<td><label for="deliverycode_enabled"><?php _e('Enable Delivery Code', 'formidable') ?></label></td>
        </tr>
	
		<?php 
			foreach(self::$form_keys as $key => $key_name){
				?>
				
				<tr class="formidable-tracing">
					<td><label><?php echo $key_name; ?></label></td>
					<td>
						<p>self <?php echo self::get_select_field($key, $form_fields, $values); ?></p>
						<p> child <?php echo self::get_input_field($key, $form_fields, $values);?></p> 
					</td>
				</tr>
				
				<?php 
			}
		?>
	</table>
	
</div>

