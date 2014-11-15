<?php
/**
 * Plugin Name: WP Google Authenticator
 * Plugin URI: https://github.com/julien731/WP-Google-Authenticator
 * Description: WP Google Authenticator provides a safe way to add 2-factor authentication to your WordPress site using the Google 2FA system with the Google Authenticator app.
 * Version: 1.1.0
 * Author: Julien Liabeuf
 * Author URI: http://julienliabeuf.com/
 * License: GPL3
 */

/* Define all the plugin constants */
define( 'WPGA_VERSION',  '1.1.0' );
define( 'WPGA_NAME',     'WP Google Authenticator' );
define( 'WPGA_AUTHOR',   'Julien Liabeuf' );
define( 'WPGA_URI',      'http://julienliabeuf.com' );
define( 'WPGA_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPGA_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPGA_PREFIX',   'wpga' );
define( 'WPGA_BASENAME', plugin_basename(__FILE__) );
define( 'WPGA_LOG',      false );
define( 'TAV_SHORTNAME', 'tav' );

require( WPGA_PATH . 'admin/admin.class.php' );
require( WPGA_PATH . 'admin/settings.class.php' );
require( WPGA_PATH . 'admin/functions-apps-passwords.php' );
add_action( 'plugins_loaded', array( 'WPGA_Admin', 'get_instance' ) );

register_activation_hook( __FILE__, 'wpga_installPlugin' );
/**
 * Register settings on plugin activation
 */
function wpga_installPlugin() {

	$defaults = array(
		'blog_name' 		=> get_bloginfo( 'name' ),
		'max_attempts' 		=> 3,
		'authorized_delay' 	=> 0,
		);

	if( !get_option( WPGA_PREFIX . '_options' ) )
		update_option( WPGA_PREFIX . '_options', $defaults );

	/* Add a new cron hook */
	if ( ! wp_next_scheduled( 'wpas_clean_totps' ) ) {
		wp_schedule_event( time(), 'daily', 'wpas_clean_totps' );
	}

}

register_uninstall_hook( __FILE__, 'wpga_uninstallPlugin' );
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
	if( !empty( $users->results ) ) {

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