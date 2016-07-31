<?php
/**
 * @package   WP Google Authenticator/Admin/Functions/Secret
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
 * Generate a secret key based on allowed chars
 * base32 compatible.
 *
 * @return string Secret key
 */
function wpga_generate_secret_key() {

	$validChars = wpga_get_valid_chars();
	$key_length = apply_filters( 'wpga_secret_key_length', 16 );

	unset( $validChars[32] );

	$secret = '';

	for ( $i = 0; $i < $key_length; $i ++ ) {

		$secret .= $validChars[ array_rand( $validChars ) ];

	}

	return $secret;

}

/**
 * List the base32 valid chars that can be used for
 * secret key generation.
 *
 * @return array Valid chars
 */
function wpga_get_valid_chars() {

	return array(
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
		'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
		'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
		'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
		'='  // padding char
	);
}

add_action( 'init', 'wpga_edit_secret' );
/**
 * Edit secret key
 *
 * This function will process various actions on the user's
 * secret key such as regenerate or revoke it. All actions
 * are checked against a nonce before doing anything.
 */
function wpga_edit_secret() {

	if( ! isset( $_GET['action'] ) ) {
		return;
	}

	switch( $_GET['action'] ):

		case 'regenerate':

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'regenerate_key' ) ) {
				return;
			}

			delete_user_meta( 'get_current_user_id()', 'wpga_secret' );
			update_user_meta( get_current_user_id(), 'wpga_secret', wpga_generate_secret_key() );
			wp_redirect( add_query_arg( array( 'update' => '10' ), admin_url( 'profile.php#wpga' ) ) );
			exit;

			break;

		case 'revoke':

			if ( ! isset( $_GET['user_id'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'revoke_key' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_user', $_GET['user_id'] ) ) {
				return;
			}

			delete_user_meta( $_GET['user_id'], 'wpga_secret' );
			delete_user_meta( $_GET['user_id'], 'wpga_backup_key' );
			wp_redirect( add_query_arg( array( 'user_id' => $_GET['user_id'], 'update' => '11' ), admin_url( 'user-edit.php' ) ) );
			exit;

			break;

		case 'reset':

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'reset_key' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_user', $_GET['user_id'] ) ) {
				return;
			}

			delete_user_meta( $_GET['user_id'], 'wpga_attempts' );
			delete_user_meta( $_GET['user_id'], 'wpga_backup_key' );
			wp_redirect( add_query_arg( array( 'user_id' => $_GET['user_id'], 'update' => '12' ), admin_url( 'user-edit.php' ) ) );
			exit;

			break;

	endswitch;

}