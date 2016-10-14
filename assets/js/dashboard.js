(function ($) {

    "use strict";

    // Setup the state of things on page load
    setup();

    // Toggle the validation fields on and off depending on the 2FA activation option
    $('#wpga-enable-2fa').click(function () {
        $("#wpga-otp-validation-wrapper").toggle(this.checked);
    }).on('switch-change', function (e, state) { // This is used for compatibility with Bootstrap Switch
        $("#wpga-otp-validation-wrapper").toggle(this.checked);
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
                $('#wpga-otp-validation-wrapper').show();
            }
        }

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

})(jQuery);