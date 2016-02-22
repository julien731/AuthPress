<?php
/**
 * @package   WP Google Authenticator/Admin/Install
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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

	if ( ! get_option( WPGA_PREFIX . '_options' ) )
		update_option( WPGA_PREFIX . '_options', $defaults );

	/* Add a new cron hook */
	if ( ! wp_next_scheduled( 'wpas_clean_totps' ) ) {
		wp_schedule_event( time(), 'daily', 'wpas_clean_totps' );
	}

}