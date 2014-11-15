jQuery(document).ready(function($) {

	$('.wpga-show-recovery').on('click', ajaxSubmit);
	$('.wpga-check-password').on('click', checkPassword);

	/* Toggle the user roles option depending on the 3force Use" status */
	$('#force_2fa_yes').on('click', check_2fa);
	if( $('#force_2fa_yes').is(':checked') ) {
		force_2fa();
	} else {
		force_2fa_reverse();
	}

	/* Toggle roles list depending on the status (all or custom) */
	if( $('#user_roles_all').is(':checked') ) { $('#wpga-all-roles').hide(); }
	$('#user_roles_all').on('click', function() { $('#wpga-all-roles').hide(); } );
	$('#user_roles_custom').on('click', function() { $('#wpga-all-roles').show(); } );

	function checkPassword() {
		$('#wpga-recovery').show();
		$('.wpga-check-pwd-link').hide();
		$('#pwd').focus();

		return false;
	}

	function check_2fa() {
		if( $('#force_2fa_yes').is(':checked') ) {
			force_2fa();
		} else {
			force_2fa_reverse();
		}
	}

	function force_2fa() {
		$('#wpga-user-roles-noforce').hide();
		$('#wpga-user-roles').show();
	}

	function force_2fa_reverse() {
		$('#wpga-user-roles-noforce').show();
		$('#wpga-user-roles').hide();
	}

	function ajaxSubmit(){

		var data = {
			action: 'wpga_get_recovery',
			pwd: $('#your-profile').find('input[name="pwd"]').val()
		};

		jQuery.ajax({
			type:'POST',
			url: ajaxurl,
			data: data,
			success:function( data ){
				jQuery('#wpga-recovery').html(data);
			}
		});

		return false;
	}

});