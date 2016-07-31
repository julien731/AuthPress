<?php
/**
 * @package   WP Google Authenticator/Functions/Login
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'login_form', 'wpga_customize_login_form' );
/**
 * Add verification code field to login form.
 */
function wpga_customize_login_form() {

	if ( ! wpga_is_2fa_active() ) {
		return;
	}

	?>
	<p>
		<label for="authenticator" class="wpga-label">
			<?php esc_html_e( 'Google Authenticator', 'wpga' ); ?> <span
				data-hint="<?php esc_attr_e( 'If you have not configured 2-factor authentication, just leave this field blank and you will be logged-in as usual. If you can\'t use the Google Authenticator app for whatever reason, you can use your recovery code instead.', 'wpga' ); ?>"
				class="hint-top-s-big hint-fade"><a class="wpga-hint" href="javascript:void(0);">[?]</a></span>
			<br>
			<input id="authenticator" class="input" type="text" size="20" value="" name="totp" autocomplete="off">
		</label>
	</p>
	<?php
}