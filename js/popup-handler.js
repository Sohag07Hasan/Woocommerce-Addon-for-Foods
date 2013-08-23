/**
 * exten the functionality for the lea
 */

jQuery(document).ready(function($){
	$('#myModal').reveal();
	
	//if session is not set for 
	$('.create_a_delivery_code').bind('click', function(){
		$('#PostModal').reveal();
		return false;
	});
	
	
	//date selector
	$('#datepicker').datepicker({dateFormat: "yy-mm-dd", minDate: 0});
	//dynamically change the time selector based on selected date	
	$('#datepicker').on('change', function(){
		var date = $(this).val();
		
		//ajax arequest sending
		$.ajax({
			
			type: 'post',
			url: SuperstoreAjax.ajax_url,
			cache: false,
			timeout: 100000,
			
			data: {
				action: 'superstore_date_changed',
				date: date,
			},
			
			success: function(result){
				//alert(result.length);
				if(result.length > 0){
					$('#delivery_time_selector').attr('title', 'You can choose any time');
				}
				else{
					$('#delivery_time_selector').attr('title', 'Please choose another Date');
				}
				$('#delivery_time_selector').html(result);
			},
			
			error: function(jqXHR, textStatus, errorThrown){
				jQuery('#site-generator').html(textStatus);
				alert(textStatus);
				return false;
			}
			
		});
		
	});
	
	
	//if the form is submitted 
	$('#date_time_selection_form_submit').bind('click', function(){
		var date = $('input[name="delivery_time"]').val();
		var time = $('#delivery_time_selector').val();
		
		if(date == null || time == null || time.length == 0){
			alert('Please choose another date and time pair');
			return false;
		}
		else{
			$(this).parents('form').submit();
		}
				
	});
	
});