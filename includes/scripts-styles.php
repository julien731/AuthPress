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
 * @global string $pagenow
 * @return void
 */
function wpga_load_admin_scripts() {

	global $pagenow;

	wp_register_script( 'wpga-bootstrap', WPGA_URL . 'assets/js/bootstrap.min.js', array( 'jquery' ), '3.3.5', true );
	wp_register_script( 'wpga-bootstrap-switch', WPGA_URL . 'assets/js/bootstrap-switch.js', array( 'jquery', 'wpga-bootstrap' ), '2.0.0', true );
	wp_register_script( 'wpga-toggle-init', WPGA_URL . 'assets/js/toggle-init.js', array( 'jquery', 'wpga-bootstrap-switch' ), WPGA_VERSION, true );
	wp_register_script( 'wpga-dashboard', WPGA_URL . 'assets/js/dashboard.js', array( 'jquery', 'wpga-bootstrap-switch' ), WPGA_VERSION, true );
	wp_register_script( 'wpga-qrcode', WPGA_URL . 'assets/js/jquery-qrcode.min.js', array( 'jquery' ), '0.14.0', true );

	if ( 'profile.php' === $pagenow || isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'wpga_apps_passwords', 'wpga-settings' ) ) ) {
		wp_enqueue_script( 'wpga-custom', WPGA_URL . 'assets/js/custom.js', array(), WPGA_VERSION, true );
		wp_enqueue_script( 'wpga-qrcode' );
	}

	if ( 'users.php' === $pagenow && isset( $_GET['page'] ) && 'authpress' === $_GET['page'] ) {
		wp_enqueue_script( 'wpga-bootstrap' );
		wp_enqueue_script( 'wpga-bootstrap-switch' );
		wp_enqueue_script( 'wpga-toggle-init' );
		wp_enqueue_script( 'wpga-dashboard' );
		wp_enqueue_script( 'wpga-qrcode' );
	}

}

add_action( 'login_enqueue_scripts', 'wpga_load_styles' );
add_action( 'admin_enqueue_scripts', 'wpga_load_styles' );
/**
 * Load the scripts resources on the login page used for the tooltip
 *
 * @since 1.0
 * @global string $pagenow
 * @return void
 */
function wpga_load_styles() {

	global $pagenow;

	wp_register_style( 'wpga-bootstrap', WPGA_URL . 'assets/css/bootstrap.min.css', null, '3.3.5', 'all' );
	wp_register_style( 'wpga-bootstrap-reset', WPGA_URL . 'assets/css/bootstrap-reset.css', null, WPGA_VERSION, 'all' );
	wp_register_style( 'wpga-bootstrap-switch', WPGA_URL . 'assets/css/bootstrap-switch.css', null, '2.0.0', 'all' );
	wp_register_style( 'wpga-style', WPGA_URL . 'assets/css/style.css', null, WPGA_VERSION, 'all' );
	wp_register_style( 'wpga-style-responsive', WPGA_URL . 'assets/css/style-responsive.css', null, WPGA_VERSION, 'all' );
	wp_register_style( 'wpga-font-awesome', WPGA_URL . 'assets/css/vendors/font-awesome/css/font-awesome.css', null, '4.6.3', 'all' );

	if ( in_array( $pagenow, array( 'wp-login.php', 'users.php' ) ) ) {

		wp_enqueue_style( 'wpga-simple-hint', WPGA_URL . 'assets/css/wpga.css', array(), null, 'all' );

		if ( isset( $_GET['page'] ) && 'authpress' === $_GET['page'] ) {
			wp_enqueue_style( 'wpga-bootstrap' );
			wp_enqueue_style( 'wpga-bootstrap-reset' );
			wp_enqueue_style( 'wpga-font-awesome' );
			wp_enqueue_style( 'wpga-bootstrap-switch' );
			wp_enqueue_style( 'wpga-style' );
			wp_enqueue_style( 'wpga-style-responsive' );
		}

	}

}