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

	global $current_user;

	// Make sure that the option is prefixed
	if ( 'wpga_' !== substr( $option, 0, 4 ) ) {
		$option = 'wpga_' . $option;
	}

	$meta = get_user_meta( $current_user->ID, $option, true );

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

	global $current_user;

	return get_transient( 'wpga_tmp_secret_' . $current_user->ID );

}