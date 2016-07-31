<?php
/**
 * @package   WP Google Authenticator/Scripts
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'admin_print_scripts', 'wpga_load_admin_scripts' );
/**
 * Load the plugin custom JS
 *
 * @since 1.0.4
 * @return void
 */
function wpga_load_admin_scripts() {

	global $pagenow;

	if ( 'profile.php' === $pagenow || isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'wpga_apps_passwords', 'wpga-settings' ) ) ) {
		wp_enqueue_script( 'wpga-custom', WPGA_URL . 'assets/js/custom.js', array(), WPGA_VERSION, true );
		wp_enqueue_script( 'wpga-qrcode', WPGA_URL . 'assets/js/jquery-qrcode.min.js', array( 'jquery' ), '0.14.0', true );
	}
}

add_action( 'login_enqueue_scripts', 'wpga_load_styles' );
add_action( 'admin_enqueue_scripts', 'wpga_load_styles' );
/**
 * Load the scripts resources on the login page used for the tooltip
 */
function wpga_load_styles() {

	global $pagenow;

	if ( in_array( $pagenow, array( 'wp-login.php', 'users.php' ) ) ) {
		wp_enqueue_style( 'wpga-simple-hint', WPGA_URL . 'assets/css/wpga.css', array(), null, 'all' );
	}

}