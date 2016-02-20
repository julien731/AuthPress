jQuery(document).ready(function($) {

	$('.wpga-show-recovery').on('click', ajaxSubmit);
	$('.wpga-check-password').on('click', checkPassword);
	$('.wpas-generate-app-pwd').on('click', generateAppPwd);
	$('.wpgas-generate-key').on('click', enableKeyField);
	$('.wpga_generate_qrcode').on('click', populateQrcode);

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

	/**
	 * Grab the TOTP data from the DOM and call
	 * jquery plugin to render it using <canvas> tags
	 * see https://larsjung.de/jquery-qrcode/ for more info.
	 */
	function populateQrcode(targetId){
		var totp = $('#wpga-qr-code p').attr('totp');
		$('#wpga-qr-code p').html('').qrcode({
			"ecLevel": "M",
			"size": 300,
			"text": totp});
	}

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

	function enableKeyField(e){
		$("#wpga_secret").prop('disabled',false);
	}

	function ajaxSubmit() {

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

	function generateAppPwd() {

		var data = {
			action: 'wpga_create_app_password',
			description: $('#wpga-new-app-pwd').find('input[name="wpga_app_password_desc"]').val()
		};

		jQuery.ajax({
			type:'POST',
			url: ajaxurl,
			data: data,
			success:function( data ){
				data = urldecode(data);
				var result = jQuery.parseJSON(data);

				/* Replace content with new password values */
				jQuery('#wpga-app-pwd').html(result.pwd);
				jQuery('#wpas-extra-row-description').html(result.desc);

				/* Hide the now useless data blocks */
				$('#wpga-new-app-pwd').hide();
				$('#wpga-app-pwd-description').hide();

				/* Display the newly created password */
				$('#wpga-app-pwd-container').show();
				$('#wpas-extra-row').show();

			}
		});

		return false;

	}

	function urldecode(str) {
		return decodeURIComponent((str + '')
			.replace(/%(?![\da-f]{2})/gi, function() {
				return '%25';
			})
			.replace(/\+/g, '%20'));
	}

});