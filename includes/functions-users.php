<?php
/**
 * WP Google Authenticator
 *
 * @package   WP Google Authenticator/Functions/Users
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2016 Julien Liabeuf
 */
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'manage_users_columns', 'wpga_add_user_2fa_column' );
/**
 * Add a new column to the users list screen for displaying the 2FA status
 *
 * @since 1.2.0
 *
 * @param array $columns Existing columns
 *
 * @return array
 */
function wpga_add_user_2fa_column( $columns ) {

	$new = array();

	foreach ( $columns as $column => $title ) {

		$new[$column] = $title;

		if ( 'role' === $column ) {
			$new['2fa'] = esc_html__( '2FA', 'wpga' );
		}

	}

	return $new;
}

add_action( 'manage_users_custom_column', 'wpga_2fa_usr_column_content', 10, 3 );
/**
 * Display the content of the 2FA column
 *
 * Checks if 2FA is enabled for the current user and displays a tag if it it.
 *
 * @since 1.2.0
 *
 * @param string $value       The column output
 * @param string $column_name Column ID
 * @param int    $user_id     Current user ID
 *
 * @return string
 */
function wpga_2fa_usr_column_content( $value, $column_name, $user_id ) {

	$user    = get_user_by( 'ID', $user_id );
	$enabled = wpga_is_2fa_active( $user );

	if ( true === $enabled ) {
		$value = sprintf( '<span class="%1$s"><abbr title="%3$s">%2$s</abbr></span>', 'wpga-tag', esc_html__( '2FA', 'wpga' ), esc_html__( '2 Factor Authentication is enabled for this user', 'wpga' ) );
	}

	return $value;
}

/**
 * Get user option
 *
 * Helper function that relies on wpga_get_user_option() and adds a couple of extra operations.
 *
 * @since 1.2
 *
 * @param string $option  Name of the option to retrieve
 * @param mixed  $default Default value to return if the option doesn't exist
 *
 * @return mixed
 */
function wpga_get_user_option( $option, $default = false ) {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Make sure that the option is prefixed
	if ( 'wpga_' !== substr( $option, 0, 4 ) ) {
		$option = 'wpga_' . $option;
	}

	$meta = get_user_meta( get_current_user_id(), $option, true );

	if ( '' === $meta ) {
		$meta = $default;
	}

	return apply_filters( 'wpga_get_user_option', $meta, $option );

}

/**
 * Get the user temporary secret
 *
 * Once the user enabled 2FA, (s)he needs to validate it by entering a OTP. Until this is done, the user's secret will
 * not be saved definitely and instead will be stored as a transient.
 *
 * This function is a helper for getting, if it exists, the temporary secret for this user from the transients.
 *
 * @since 1.2
 * @return false|string
 */
function wpga_get_user_temp_secret() {
	return get_transient( 'wpga_tmp_secret_' . get_current_user_id() );
}

add_action( 'wp_ajax_wpga_get_user_temp_password', 'wpga_get_user_temp_secret_ajax' );
/**
 * Get the user temporary secret via Ajax
 *
 * A simple wrapper for wpga_get_user_temp_secret() to be used in AJax processes.
 *
 * @since 2.0
 * @return void
 */
function wpga_get_user_temp_secret_ajax() {
	echo wpga_get_user_temp_secret();
	die();
}

add_action( 'wp_ajax_wpga_setup_secret', 'wpga_setup_secret' );
/**
 * Setup the user secret key
 *
 * If the secret hasn't been confirmed, we simply generate it and save it
 *
 * @since 1.2
 *
 * @param bool $confirmed Whether or not the secret has been confirmed
 */
function wpga_setup_secret( $confirmed = false ) {

	$secret = wpga_get_user_option( 'secret' );

	// If the secret has already been generated and validated, we do nothing
	if ( is_string( $secret ) && ! empty( $secret ) ) {
		return;
	}

	$temp    = wpga_get_user_temp_secret();
	$user_id = get_current_user_id();

	if ( false === $temp ) {

		$key = wpga_generate_secret_key();

		if ( true === $confirmed ) {
			add_user_meta( $user_id, 'wpga_secret', $key );
		} else {
			set_transient( 'wpga_tmp_secret_' . $user_id, $key, 86400 ); // Set the transient for 24 hours
		}

	} else {
		if ( true === $confirmed ) {
			add_user_meta( $user_id, 'wpga_secret', $temp );
			delete_transient( 'wpga_tmp_secret_' . $user_id );
		}
	}

}

add_action( 'wp_ajax_wpga_disable_2fa', 'wpga_disable_2fa' );
/**
 * Disable 2FA for a particular user
 *
 * @since      1.2.0
 *
 * @return void
 */
function wpga_disable_2fa() {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( get_current_user_id() );
	$user->deactivate_2fa();

}

add_action( 'init', 'wpga_opt_confirm' );
/**
 * Confirm the user OTP
 *
 * Process the user submitted OTP, make sure it is valid and, if it is, set the OTP as final.
 *
 * @since 1.2
 * @return void
 */
function wpga_opt_confirm() {

	if ( ! isset( $_POST['wpga_otp_confirm'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['wpga_otp_confirm'], 'validate_otp' ) ) {
		return;
	}

	$otp    = filter_input( INPUT_POST, 'wpga_otp', FILTER_SANITIZE_NUMBER_INT );
	$user   = new WPGA_User( get_current_user_id() );
	$result = false;

	if ( true === $user->is_otp_valid( $otp, true ) ) {
		$user->set_final_otp();
		$result = true;
	}

	wp_safe_redirect( add_query_arg( array(
		'page'    => 'authpress',
		'message' => true === $result ? 'otp_confirmed' : 'otp_failed',
	), admin_url( 'users.php' ) ) );

	exit;

}