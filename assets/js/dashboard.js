(function ($) {

    "use strict";

    // Setup the state of things on page load
    setup();

    // Toggle the validation fields on and off depending on the 2FA activation option
    $('#wpga-enable-2fa').click(function () {
        $("#wpga-otp-validation-wrapper").toggle(this.checked);
        if (true === this.checked) {
            user_setup();
        } else {
            disable_2fa();
        }
    }).on('switch-change', function (e, state) { // This is used for compatibility with Bootstrap Switch
        $("#wpga-otp-validation-wrapper").toggle(this.checked);
        if (true === this.checked) {
            user_setup();
        } else {
            disable_2fa();
        }
    });

    /**
     * The setup function sets the state of all elements on page load
     *
     * @since 1.2
     */
    function setup() {

        // Show validation fields if necessary
        if ($('#wpga-enable-2fa').is(':checked')) {

            var tmp_pwd = get_user_temp_password();

            if ('string' === typeof tmp_pwd) {
                setup_qr_code();
                $('#wpga-otp-validation-wrapper').show();
            }
        }

    }

    /**
     * Run the tasks required to setup 2FA for the user
     *
     * @since 1.2
     */
    function user_setup() {
        setup_secret();
        setup_qr_code();
        $('#wpga-activation-warning').show();
    }

    /**
     * Setup the user secret key
     *
     * @since 1.2
     * @param {bool} state
     */
    function setup_secret() {
        var data = {
            action: 'wpga_setup_secret'
        };

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data
        });
    }

    /**
     * Get the QR code URI
     *
     * @since 1.2
     */
    function setup_qr_code() {

        var data = {
            action: 'wpga_get_qr_code_uri'
        };

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (data) {
                $('#wpga-2fa-validation-qr').html('').qrcode({
                    "ecLevel": "M",
                    "size": 300,
                    "text": data.data
                });
            }
        });

    }

    /**
     * Get the user temporary secret
     *
     * @since 1.2
     * @returns {boolean|string}
     */
    function get_user_temp_password() {

        var result = false;

        var data = {
            action: 'wpga_get_user_temp_password'
        };

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (data) {
                result = data;
            }
        });

        return result;

    }

    /**
     * Disable 2-factor authentication for the current user
     *
     * @since 1.2
     */
    function disable_2fa() {
        var data = {
            action: 'wpga_disable_2fa'
        };

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data
        });
    }

})(jQuery);