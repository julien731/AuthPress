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

/**
 * Get app passwords for a user
 *
 * @since 1.1
 *
 * @param null|int $user_id
 *
 * @return array
 */
function wpga_get_app_passwords( $user_id = null ) {

	if ( is_null( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	$user = new WPGA_User( $user_id );

	return $user->get_app_passwords();

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

/**
 * Delete a user's app password
 *
 * @since 1.1
 *
 * @param string $id ID of the key to delete
 *
 * @return bool
 */
function wpga_delete_app_password( $id ) {
	return WPGA()->recovery->delete_key( $id );
}

/**
 * Delete all of a user's app passwords at one
 *
 * @since 1.1
 * @return bool
 */
function wpga_reset_app_passwords() {

	global $current_user;

	$result = true;

	$keys = WPGA()->recovery->get_key_by( 'user_id', $current_user->ID, false, 'app_password' );

	if ( is_array( $keys ) ) {
		foreach ( $keys as $key ) {
			if ( ! wpga_delete_app_password( $key['ID'] ) ) {
				$result = false;
			}
		}
	}

	return $result;

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

	$pwd     = WPGA()->recovery->generate_key();
	$app_pwd = WPGA()->recovery->add_key( $current_user->ID, $pwd, sanitize_text_field( $_POST['description'] ), 'app_password' );

	if ( false !== $app_pwd ) {
		$return = json_encode( array(
			'desc' => sanitize_text_field( $_POST['description'] ),
			'pwd'  => esc_attr( $pwd ),
		) );
	} else {
		$return = json_encode( array( 'error' ) );
	}

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

/**
 * Create the custom database table to store recovery keys
 *
 * @since 1.2
 * @return void
 */
function wpga_apps_access_log_create_table() {

	global $wpdb;

	$table           = wpga_apps_access_log_table;
	$charset_collate = $wpdb->get_charset_collate();

	// Prepare DB structure if not already existing
	if ( $wpdb->get_var( "show tables like '$table'" ) != $table ) {

		$sql = "CREATE TABLE $table (
				ID mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id mediumint(9) NOT NULL,
				key_id mediumint(9) NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				ip VARCHAR(255) NOT NULL,
				user_agent VARCHAR(255),
				method VARCHAR(20),
				UNIQUE KEY ID  (ID)
				) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Save database version. Useful for upgrades.
		add_option( 'wpga_db_version', WPGA_DB_VERSION );

	}

}