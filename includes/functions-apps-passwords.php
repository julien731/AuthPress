<?php
/**
 * @package   WP Google Authenticator/Functions/Apps Passwords
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
 * Generate a unique key.
 *
 * The key is used to identify the app password.
 * It is an unchangeable unique value.
 *
 * @since  1.1.0
 * @param  string $hash Hash of the newly created password
 * @return string       Unique identifying key
 */
function wpga_make_unique_key( $hash ) {

	$passwords = wpga_get_app_passwords();
	$key       = substr( $hash, 0, 5 );

	if ( !array_key_exists( $key, $passwords ) ) {
		return $key;
	}

	$index = 0;
	$key   = $key . $index;

	while ( array_key_exists( $key, $passwords ) ) {
		++$index;
		$key = substr( $key, 0, 5 ) . $index;
	}

	return $key;

}

function wpga_get_last_access( $key ) {

	global $current_user;

	$log  = wpga_get_app_passwords_log();
	$last = array();

	if ( empty( $log ) ) {
		return false;
	}

	foreach ( $log as $date => $entry ) {
		if ( $key === $entry['key'] ) {
			array_push( $last, $entry );
		}
	}

	if ( empty( $last ) ) {
		return false;
	}

	$count = count( $last ) - 1;

	return $last[$count];

}

function wpga_get_app_passwords( $user_id = null ) {

	if ( is_null( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	$passwords = is_array( $p = get_user_meta( $user_id, 'wpga_apps_passwords', true ) ) ? $p : array();
	return $passwords;
}

function wpga_get_app_passwords_log( $user_id = null ) {

	if ( is_null( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	$log = is_array( $p = get_user_meta( $user_id, 'wpga_apps_passwords_log', true ) ) ? $p : array();
	krsort( $log );

	return $log;

}

function wpga_delete_app_password( $key ) {

	global $current_user;

	$passwords = $new = wpga_get_app_passwords();

	if ( array_key_exists( $key, $passwords ) ) {
		unset( $new[$key] );
		update_user_meta( $current_user->ID, 'wpga_apps_passwords', $new, $passwords );
	}

}

function wpga_reset_app_passwords() {
	global $current_user;
	delete_user_meta( $current_user->ID, 'wpga_apps_passwords' );
}

function wpga_clear_log() {
	global $current_user;
	delete_user_meta( $current_user->ID, 'wpga_apps_passwords_log' );
}

add_action( 'wp_ajax_wpga_create_app_password', 'wpga_create_app_password' );
/**
 * Create a new app password.
 *
 * @since  1.1.0
 */
function wpga_create_app_password() {

	if ( ! isset( $_POST['description'] ) || empty( $_POST['description'] ) ) {
		die();
	}

	global $current_user;

	$passwords = $new = is_array( $p = get_user_meta( $current_user->ID, 'wpga_apps_passwords', true ) ) ? $p : array();
	$pwd       = wpga_generate_backup_key();
	$hash      = md5( esc_attr( $pwd ) );
	$key       = wpga_make_unique_key( $hash );
	$return    = json_encode( array( 'desc' => sanitize_text_field( $_POST['description'] ), 'pwd' => esc_attr( $pwd ) ) );
	$new[$key] = array( 'description' => sanitize_text_field( $_POST['description'] ), 'hash' => $hash, 'count' => 0 );

	update_user_meta( $current_user->ID, 'wpga_apps_passwords', $new, $passwords );

	echo esc_attr( urlencode( $return ) );
	die();

}

add_action( 'admin_menu', 'wpga_add_app_password_menu' );
/**
 * Add required menu items
 */
function wpga_add_app_password_menu() {
	add_users_page(
		esc_html__( 'Google Authenticator Applications Passwords', 'wpga' ),
		esc_html__( 'My Apps Passwords', 'wpga' ),
		'read',
		WPGA_PREFIX . '_apps_passwords',
		'wpga_apps_passwords_display'
	);
}

/**
 * Display the applications passwords apge.
 *
 * @since 1.1.0
 */
function wpga_apps_passwords_display() {
	require_once( WPGA_PATH . 'includes/admin/views/apps-passwords.php' );
}

add_action( 'admin_init', 'wpas_apps_passwords_actions' );
/**
 * Run app passwords related actions.
 *
 * Run the actions and redirect to the user's page
 * in "read only" mode, without the URL vars that can cause
 * undesired actions (like clearing the log again).
 *
 * @since  1.1.0
 * @return void
 */
function wpas_apps_passwords_actions() {

	if ( isset( $_GET['action'] ) && isset( $_GET['wpga_nonce'] ) ) {

		if ( wp_verify_nonce( $_GET['wpga_nonce'], 'wpga_action' ) ) {

			switch ( $_GET['action'] ) {
				case 'delete':

					if ( isset( $_GET['key'] ) ) {
						$delete_key = sanitize_key( $_GET['key'] );
						wpga_delete_app_password( $delete_key );
					}

					break;

				case 'delete_all':
					wpga_reset_app_passwords();
					break;

				case 'clear_log':
					wpga_clear_log();
					break;

			}

		}

		wp_redirect( add_query_arg( array( 'page' => 'wpga_apps_passwords' ), admin_url( 'users.php') ) );
		exit;

	}

}