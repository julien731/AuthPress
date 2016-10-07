<?php
/**
 * WP Google Authenticator
 *
 * @package   WP Google Authenticator/Functions/Deprecated
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      http://themeavenue.net
 * @copyright 2016 Julien Liabeuf
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the number of login attempts done by a user
 *
 * @since      1.2.0
 *
 * @param int $user_id
 *
 * @deprecated 1.2
 *
 * @return int
 */
function wpas_get_login_attempts( $user_id ) {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( $user_id );

	return $user->login_attempts();

}

/**
 * Get the number of remaining login attempts for a user
 *
 * @since      1.2.0
 *
 * @param $user_id
 *
 * @deprecated 1.2
 *
 * @return int
 */
function wpas_get_remaining_login_attempts( $user_id ) {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( $user_id );

	return $user->remaining_attempts();

}

/**
 * Increment the number of login attempts done by a user
 *
 * @since      1.2.0
 *
 * @param $user_id
 *
 * @deprecated 1.2
 *
 * @return int
 */
function wpas_increment_attempts( $user_id ) {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( $user_id );

	return $user->add_attempt();

}

/**
 * Disable 2FA for a particular user
 *
 * @since      1.2.0
 *
 * @param $user_id
 *
 * @deprecated 1.2
 *
 * @return void
 */
function wpga_disable_2fa( $user_id ) {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( $user_id );
	$user->deactivate_2fa();

}

/**
 * Check validity of a recovery key
 *
 * @since      1.0.4
 *
 * @param  object $user User object
 * @param  string $key  Recovery key to check
 *
 * @deprecated 1.2
 *
 * @return boolean      Whether or not the key is valid
 */
function wpga_check_recovery_key( $user, $key ) {

	/**
	 * @var WPGA_User
	 */
	$user = new WPGA_User( $user );

	return $user->is_recovery_key( $key );

}

/**
 * Generate a backup key
 *
 * In case the user loses his phone or cannot access the Google Authenticator app,
 * we generate a unique backup key that the user can use to authenticate once.
 * After one (only) authentication the key will be voided.
 *
 * @since      1.0.4
 * @deprecated 1.2
 * @return string Backup key
 */
function wpga_generate_backup_key() {
	return WPGA()->recovery->generate_key();
}