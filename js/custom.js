jQuery(document).ready(function($) {

	$(".wpga-show-recovery").on('click', ajaxSubmit);
	$(".wpga-check-password").on('click', checkPassword);

	function checkPassword() {

		$("#wpga-recovery").show();
		$(".wpga-check-pwd-link").hide();
		$('#pwd').focus();

		return false;

	}

	function ajaxSubmit(){

		 var data = {
        	action: 'wpga_get_recovery',
        	pwd: $('#your-profile').find('input[name="pwd"]').val()
        };

		jQuery.ajax({
			type:"POST",
			url: ajaxurl,
			data: data,
			success:function( data ){
				jQuery("#wpga-recovery").html(data);
			}
		});

		return false;
	}

});