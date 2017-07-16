<?php
/**
 * @package   WP Google Authenticator/Uninstall
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

wpga_uninstallPlugin();
/**
 * Remove plugin data from database
 */
function wpga_uninstallPlugin() {

	/* Plugin main options */
	delete_option( WPGA_PREFIX . '_options' );
	delete_option( WPGA_PREFIX . '_used_totp' );

	$args = array( 'meta_query' => array(
		'relation' => 'OR',
		array(
			'key'     => 'wpga_attempts',
			'value'   => '',
			'compare' => '!='
		),
		array(
			'key'     => 'wpga_secret',
			'value'   => '',
			'compare' => '!='
		)
	)
	);

	$users = new WP_User_Query( $args );

	/* Delete all user metas */
	if ( ! empty( $users->results ) ) {

		foreach( $users->results as $key => $user ) {

			delete_user_meta( $user->ID, 'wpga_active' );
			delete_user_meta( $user->ID, 'wpga_attempts' );
			delete_user_meta( $user->ID, 'wpga_secret' );
			delete_user_meta( $user->ID, 'wpga_backup_key' );
			delete_user_meta( $user->ID, 'wpga_backup_key_time' );
			delete_user_meta( $user->ID, 'wpga_apps_passwords' );
			delete_user_meta( $user->ID, 'wpga_apps_passwords_log' );

		}

	}

	/**
	 * Remove cron task
	 */
	$timestamp = wp_next_scheduled( 'wpas_clean_totps' );
	wp_unschedule_event( $timestamp, 'wpas_clean_totps' );

}