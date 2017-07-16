<?php
/**
 * WP Google Authenticator
 *
 * @package   WP Google Authenticator/Functions/Users
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2016 Julien Liabeuf
 */
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'manage_users_columns', 'wpga_add_user_2fa_column' );
/**
 * Add a new column to the users list screen for displaying the 2FA status
 *
 * @since 1.2.0
 *
 * @param array $columns Existing columns
 *
 * @return array
 */
function wpga_add_user_2fa_column( $columns ) {

	$new = array();

	foreach ( $columns as $column => $title ) {

		$new[$column] = $title;

		if ( 'role' === $column ) {
			$new['2fa'] = esc_html__( '2FA', 'wpga' );
		}

	}

	return $new;
}

add_action( 'manage_users_custom_column', 'wpga_2fa_usr_column_content', 10, 3 );
/**
 * Display the content of the 2FA column
 *
 * Checks if 2FA is enabled for the current user and displays a tag if it it.
 *
 * @since 1.2.0
 *
 * @param string $value       The column output
 * @param string $column_name Column ID
 * @param int    $user_id     Current user ID
 *
 * @return string
 */
function wpga_2fa_usr_column_content( $value, $column_name, $user_id ) {

	$user    = get_user_by( 'ID', $user_id );
	$enabled = wpga_is_2fa_active( $user );

	if ( true === $enabled ) {
		$value = sprintf( '<span class="%1$s"><abbr title="%3$s">%2$s</abbr></span>', 'wpga-tag', esc_html__( '2FA', 'wpga' ), esc_html__( '2 Factor Authentication is enabled for this user', 'wpga' ) );
	}

	return $value;
}