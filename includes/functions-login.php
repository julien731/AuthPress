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

//add_action( 'login_form', 'wpga_customize_login_form' );
/**
 * Add verification code field to login form.
 */
function wpga_customize_login_form() {

	$options = get_option( 'wpga_options', array() );

	if ( ! isset( $options['active'] ) || ! in_array( 'yes', $options['active'] ) ) {
		return;
	}

	?>
	<p>
		<label for="authenticator" class="wpga-label">
			<?php esc_html_e( 'Google Authenticator', 'wpga' ); ?> <span
				data-hint="<?php esc_attr_e( 'If you do not have configured the 2-factor authentication, just leave this field blank and you will be logged-in as usual. If you can\'t use the Google Authenticator app for whatever reason, you can use your recovery code instead.', 'wpga' ); ?>"
				class="hint-top-s-big hint-fade"><a class="wpga-hint" href="javascript:void(0);">[?]</a></span>
			<br>
			<input id="authenticator" class="input" type="text" size="20" value="" name="totp" autocomplete="off">
		</label>
	</p>
	<?php
}

add_action( 'login_form_2fa', 'wpga_totp_prompt_screen' );
/**
 * Display the TOTP prompt screen
 *
 * @since 1.2.0
 * @return void
 */
function wpga_totp_prompt_screen() {

	// If there is no nonce or no username is provided we redirect to the login page
	if ( ! isset( $_GET['_nonce'] ) || ! isset( $_GET['u'] ) ) {
		wp_safe_redirect( wp_login_url() );
		die;
	}

	// Try and get the user
	$user = get_user_by( 'login', sanitize_text_field( $_GET['u'] ) );

	// If the login is invalid we redirect to the login page
	if ( ! is_object( $user ) || is_object( $user ) && ! is_a( $user, 'WP_User' ) ) {
		wp_safe_redirect( wp_login_url() );
		die;
	}

	// If the nonce is invalid we redirect to the login page
	if ( false === wpga_validate_nonce( $user, $_GET['_nonce'] ) ) {
		wp_safe_redirect( wp_login_url() );
		die;
	}

	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
	$remember_me = isset( $_REQUEST['remember_me'] ) ? sanitize_text_field( $_REQUEST['remember_me'] ) : '';
	$action_url  = esc_url( site_url( 'wp-login.php', 'login_post' ) );

	if ( ! empty( $remember_me ) ) {
		$action_url = add_query_arg( array( 'remember_me' => $remember_me ), $action_url );
	}

	require( WPGA_PATH . 'includes/views/totp-prompt.php' );
	die;

}