<?php
/**
 * @package   AuthPress/Admin/Helpers
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Helper function for registering dismissible admin notices.
 * The function dnh_register_notice() comes with the WP Dismissible Notices Handler library. This helper function is a
 * wrapper for dnh_register_notice().
 *
 * @since 2.0
 *
 * @param string $id      Notice ID, used to identify it
 * @param string $type    Type of notice to display
 * @param string $content Notice content
 * @param array  $args    Additional parameters
 *
 * @return void
 */
function authpress_register_notice( $id, $type, $content, $args = array() ) {
	if ( function_exists( 'dnh_register_notice' ) ) {
		dnh_register_notice( $id, $type, $content, $args );
	}
}
