<?php
/**
 * @package   WP Google Authenticator/Functions/Recovery
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Check validity of a recovery key
 *
 * @param  object $user User object
 * @param  string $key  Recovery key to check
 *
 * @return boolean      Whether or not the key is valid
 * @since  1.0.4
 */
function wpga_check_recovery_key( $user, $key ) {

	$recovery = get_user_meta( $user->ID, 'wpga_backup_key', true );

	if ( sanitize_key( $key ) == $recovery ) {
		return true;
	} else {
		return false;
	}

}

add_action( 'wp_ajax_wpga_get_recovery', 'wpga_ajax_callback' );
/**
 * Get recovery code
 *
 * The function will check the user's password and,
 * if the password is correct, it will return
 * the recovery code.
 *
 * @return void
 * @since 1.0.4
 */
function wpga_ajax_callback() {

	if ( ! isset( $_POST['pwd'] ) ) {
		return;
	}

	/* Password to check */
	$pwd = sanitize_text_field( $_POST['pwd'] );

	$user_id = get_current_user_id();
	$user    = get_user_by( 'id', $user_id );

	if ( $user && wp_check_password( $pwd, $user->data->user_pass, $user->ID ) ) {

		$recovery = get_user_meta( $user_id, 'wpga_backup_key', true );

		if ( '' != $recovery ) {
			echo "<div style='font-size:18px; font-weight: bold;'>" . esc_html( $recovery ) . "</div><p>" . esc_html_e( 'Write this down and keep it safe', 'wpga' ) . "</p>";
		} else {
			esc_html_e( 'No recovery code set yet.', 'wpga' );
		}

	} else {
		?><strong><?php esc_html_e( 'Wrong password', 'wpga' ); ?></strong><?php
	}

	die();

}

/**
 * Generate a backup key
 *
 * In case the user loses his phone or cannot access the Google Authenticator app,
 * we generate a unique backup key that the user can use to authenticate once.
 * After one (only) authentication the key will be voided.
 *
 * @return string Backup key
 * @since 1.0.4
 */
function wpga_generate_backup_key() {

	$length = apply_filters( 'wpga_recovery_code_length', 24 );;
	$max    = ceil( $length / 40 );
	$random = '';

	for ( $i = 0; $i < $max; $i ++ ) {
		$random .= sha1( microtime( true ) . mt_rand( 10000, 90000 ) );
	}

	return substr( $random, 0, $length );
}