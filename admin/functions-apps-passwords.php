<?php
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