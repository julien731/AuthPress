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
 * Helper function to get a user's recovery key
 *
 * @since 1.2
 *
 * @param int $user_id The user ID
 *
 * @return false|string
 */
function wpga_get_user_recovery_keys( $user_id ) {
	return WPGA()->recovery->get_recovery_keys( $user_id );
}

/**
 * Create the custom database table to store recovery keys
 *
 * @since 1.2
 * @return void
 */
function wpga_recovery_keys_create_table() {

	global $wpdb;

	$table           = wpga_recovery_keys_table;
	$charset_collate = $wpdb->get_charset_collate();

	// Prepare DB structure if not already existing
	if ( $wpdb->get_var( "show tables like '$table'" ) != $table ) {

		$sql = "CREATE TABLE $table (
				ID mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id mediumint(9) NOT NULL,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				code VARCHAR(255) NOT NULL,
				name VARCHAR(100),
				type VARCHAR(20) NOT NULL,
				count VARCHAR(20),
				UNIQUE KEY ID  (ID)
				) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Save database version. Useful for upgrades.
		add_option( 'wpga_db_version', WPGA_DB_VERSION );

	}

}