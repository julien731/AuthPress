<?php
/**
 * @package   WP Google Authenticator/Functions/TOTP
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
 * Revoke a TOTP
 *
 * @since 1.2.0
 *
 * @param string $totp TOTP to invalidate
 *
 * @return bool
 */
function wpga_revoke_totp( $totp ) {

	$used = get_option( 'wpga_used_totp', array() );

	if ( is_array( $used ) && ! in_array( md5( $totp ), $used ) ) {

		array_push( $used, md5( $totp ) );

		update_option( 'wpga_used_totp', $used );

		return true;

	}

	return false;

}

/**
 * Calculate the code, with given secret and point in time
 *
 * @param string  $secret
 * @param integer $timeSlice
 *
 * @return string Generated code
 */
function wpga_get_code( $secret, $timeSlice = null ) {

	if ( $timeSlice === null ) {
		$timeSlice = floor( time() / 30 );
	}

	$code_length = apply_filters( 'wpga_code_length', 6 );
	$secretkey   = wpga_base32_decode( $secret );

	// Pack time into binary string
	$time = chr( 0 ) . chr( 0 ) . chr( 0 ) . chr( 0 ) . pack( 'N*', $timeSlice );

	// Hash it with users secret key
	$hm = hash_hmac( 'SHA1', $time, $secretkey, true );

	// Use last nipple of result as index/offset
	$offset = ord( substr( $hm, - 1 ) ) & 0x0F;

	// grab 4 bytes of the result
	$hashpart = substr( $hm, $offset, 4 );

	// Unpak binary value
	$value = unpack( 'N', $hashpart );
	$value = $value[1];

	// Only 32 bits
	$value = $value & 0x7FFFFFFF;

	$modulo = pow( 10, $code_length );

	return str_pad( $value % $modulo, $code_length, '0', STR_PAD_LEFT );
}

/**
 * Decode base32 string
 *
 * @param  string $string String to decode
 *
 * @return string Decoded string
 */
function wpga_base32_decode( $string ) {

	$lut = array(
		"A" => 0,
		"B" => 1,
		"C" => 2,
		"D" => 3,
		"E" => 4,
		"F" => 5,
		"G" => 6,
		"H" => 7,
		"I" => 8,
		"J" => 9,
		"K" => 10,
		"L" => 11,
		"M" => 12,
		"N" => 13,
		"O" => 14,
		"P" => 15,
		"Q" => 16,
		"R" => 17,
		"S" => 18,
		"T" => 19,
		"U" => 20,
		"V" => 21,
		"W" => 22,
		"X" => 23,
		"Y" => 24,
		"Z" => 25,
		"2" => 26,
		"3" => 27,
		"4" => 28,
		"5" => 29,
		"6" => 30,
		"7" => 31
	);

	$string = strtoupper( $string );
	$l      = strlen( $string );
	$n      = 0;
	$j      = 0;
	$binary = "";

	for ( $i = 0; $i < $l; $i ++ ) {

		$n = $n << 5;
		$n = $n + $lut[ $string[ $i ] ];
		$j = $j + 5;

		if ( $j >= 8 ) {
			$j = $j - 8;
			$binary .= chr( ( $n & ( 0xFF << $j ) ) >> $j );
		}
	}

	return $binary;
}

add_action( 'wpas_clean_totps', 'wpga_clean_totps' );
/**
 * Delete all TOTPs from DB.
 *
 * As TOTPs expire after a defined amount of time
 * per definition, there is no need to store them
 * in the database forever.
 *
 * @since 1.0.7
 */
function wpga_clean_totps() {
	delete_option( 'wpga_used_totp' );
}

/**
 * Check if 2FA is enabled on this site
 *
 * If a user object is passed, we check if 2FA is enabled on the site and for this particular user.
 *
 * @since 1.2.0
 *
 * @param WP_User|bool $user Object of the user who's trying to login
 *
 * @return bool
 */
function wpga_is_2fa_active( $user = false ) {

	$active = wpga_get_option( 'active', false );

	if ( $active && is_array( $active ) ) {
		$active = in_array( 'yes', $active ) ? true : false;
	}

	// If 2FA is enabled on the site, make sure the current user, if any, has it enabled for his account
	if ( true === $active && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
		$wpga_user = new WPGA_User( $user );
		$active    = $wpga_user->has_2fa();
	}

	return $active;

}

/**
 * Check if 2FA is being forced by the admin
 *
 * @since 1.2.0
 *
 * @param array $roles Roles to check for forced 2FA
 *
 * @return bool
 */
function wpga_is_2fa_forced( $roles = array() ) {

	$options = get_option( 'wpga_options', array() );

	/* Check if 2FA is forced by the admin */
	if ( ! isset( $options['force_2fa'] ) || ! in_array( 'yes', (array) $options['force_2fa'] ) ) {
		return false;
	}

	if ( 'all' === $options['user_role_status'] ) {
		return true;
	}

	/* If the forced roles list is empty, we consider it active for all users. Hence, we add the current user role in the list. */
	if ( ! isset( $options['user_roles'] ) || empty( $options['user_roles'] ) ) {
		$options['user_roles'] = $roles;
	}

	/* Check if 2FA is forced for the role this user has */
	if ( array_intersect( $roles, $options['user_roles'] ) ) {
		return true;
	}

	return false;

}

/**
 * Check if a OPT has already been used
 *
 * @since 1.2
 *
 * @param string $otp The OPT to check
 *
 * @return bool
 */
function wpga_was_otp_used( $otp ) {

	$used = get_option( 'wpga_used_totp', array() );

	if ( is_array( $used ) && ! in_array( md5( $totp ), $used ) ) {
		return false;
	} else {
		return true;
	}

}