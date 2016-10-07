<?php
/**
 * @package   WP Google Authenticator/Classes/User
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPGA_User {

	/**
	 * The user ID
	 *
	 * @since 1.2
	 * @var int
	 */
	public $user_id;

	/**
	 * The standard WordPress user object
	 *
	 * @since 1.2
	 * @var WP_User
	 */
	private $user;

	/**
	 * The 2FA status for the user
	 *
	 * Whether or not the user has 2FA enabled and setup. If 2FA is enabled but the user hasn't (yet) set it up, the
	 * property remains false.
	 *
	 * @since 1.2
	 * @var bool
	 */
	protected $has_2fa;

	/**
	 * Holds the number of failed login attempts
	 *
	 * @since 1.2
	 * @var int
	 */
	protected $login_attempts;

	/**
	 * Holds the number of remaining login attempts before account lockout
	 *
	 * @since 1.2
	 * @var int
	 */
	protected $remaining_attempts;

	/**
	 * User secret key
	 *
	 * @since 1.2
	 * @var string
	 */
	protected $secret;

	/**
	 * A list of the user's app passwords
	 *
	 * @since 1.2
	 * @var array
	 */
	protected $app_passwords;

	/**
	 * WPGA_User constructor
	 *
	 * @since 1.2
	 *
	 * @param int|WP_User $user
	 */
	public function __construct( $user ) {

		if ( is_object( $user ) && is_a( $user, 'WP_User' ) ) {
			$this->user_id = $user->ID;
			$this->user    = $user;
		} elseif ( is_numeric( $user ) ) {

			$this->user = get_user_by( 'id', $user );

			if ( is_object( $this->user ) && is_a( $this->user, 'WP_User' ) ) {
				$this->user_id = $this->user->ID;
			}

		}

	}

	/**
	 * Check if user has 2FA enabled
	 *
	 * @since 1.2
	 * @return bool
	 */
	public function has_2fa() {

		if ( ! is_null( $this->has_2fa ) && is_bool( $this->has_2fa ) ) {
			return $this->has_2fa;
		}

		$this->has_2fa = false;

		if ( 'yes' === get_user_meta( $this->user_id, 'wpga_active', true ) ) {
			$this->has_2fa = true;
		}

		return $this->has_2fa;

	}

	/**
	 * Get user secret key
	 *
	 * @since 1.2
	 * @return string
	 */
	public function get_secret() {

		if ( ! is_null( $this->secret ) ) {
			return $this->secret;
		}

		return $this->secret = get_user_meta( $this->user_id, 'wpga_secret', true );

	}

	/**
	 * Get the number of failed login attempts
	 *
	 * THis method returns the number of failed attempts or false if there is no failed attempts.
	 *
	 * @since 1.2
	 * @return int|false
	 */
	public function login_attempts() {

		if ( ! is_null( $this->login_attempts ) && is_int( $this->login_attempts ) ) {
			return $this->login_attempts;
		}

		$this->login_attempts = (int) get_user_meta( $this->user_id, 'wpga_attempts', true );

		if ( empty( $this->login_attempts ) ) {
			$this->login_attempts = 0;
		}

		return $this->login_attempts;

	}

	/**
	 * Get the number of attempts remaining
	 *
	 * Based on the number of failed attempts, calculate the number of attempts the user can make before locking his
	 * account.
	 *
	 * @since 1.2
	 * @return int
	 */
	public function remaining_attempts() {

		if ( ! is_null( $this->remaining_attempts ) && is_int( $this->remaining_attempts ) ) {
			return $this->remaining_attempts;
		}

		$options      = get_option( 'wpga_options', array() );
		$max_attempts = ( isset( $options['max_attempts'] ) && '' != $options['max_attempts'] ) ? (int) $options['max_attempts'] : 3;

		if ( - 1 === $max_attempts ) {
			return $max_attempts;
		}

		$this->remaining_attempts = 0; // Set the remaining attempts number ot 0 for security
		$attempts                 = $this->login_attempts();

		if ( $attempts < $max_attempts ) {
			$this->remaining_attempts = $max_attempts - $attempts;
		}

		return $this->remaining_attempts;

	}

	/**
	 * Add a new attempt to the number of failed attempts
	 *
	 * @since 1.2
	 * @return int The new number of failed attempts
	 */
	public function add_attempt() {

		$attempts = $this->login_attempts();

		// Increment in database
		update_user_meta( $this->user_id, 'wpga_attempts', $attempts + 1, $attempts );

		// Update the internal property
		$this->login_attempts = $attempts + 1;

		return $this->login_attempts;

	}

	/**
	 * Deactivate 2FA for the current user
	 *
	 * @since 1.2
	 * @return void
	 */
	public function deactivate_2fa() {
		/* Clean the 2FA data */
		delete_user_meta( $this->user_id, 'wpga_active' );
		delete_user_meta( $this->user_id, 'wpga_attempts' );
		delete_user_meta( $this->user_id, 'wpga_secret' );
		delete_user_meta( $this->user_id, 'wpga_backup_key' );
		delete_user_meta( $this->user_id, 'wpga_backup_key_time' );
	}

	/**
	 * Check the validity of a one-time password for the current user
	 *
	 * @since 1.2
	 *
	 * @param string $otp The OTP to check with this user
	 *
	 * @return bool|WP_Error
	 */
	public function is_otp_valid( $otp ) {

		// Set the validity to false for the start
		$valid = false;

		// Get the authorized discrepancy delay and calculate the allowed time range
		$options          = get_option( 'wpga_options' );
		$drift            = isset( $options['authorized_delay'] ) ? (int) $options['authorized_delay'] * 2 : 1;
		$currentTimeSlice = floor( time() / 30 );

		// Generate all OTPs for the allowed time range and compare them to the OTP we got
		for ( $i = - $drift; $i <= $drift; $i ++ ) {

			// Generate a new valid OTP
			$currently_valid_otp = wpga_get_code( $this->get_secret(), $currentTimeSlice + $i );

			if ( $currently_valid_otp === $otp ) {
				$valid = true;
				break; // If we get a match no need to generate other OTPs
			}

		}

		// If we didn't find a valid OTP no need to go any further. The login attempt is simply invalid.
		if ( false === $valid ) {
			return false;
		}

		// Make sure that the OTP hasn't been used yet, in which case we do not accept it
		if ( true === wpga_was_otp_used( $otp ) ) {
			$valid = new WP_Error( 'expired_totp', esc_html__( 'The one time password you used has already been revoked.', 'wpga' ) );
		}

		return $valid;

	}

	/**
	 * Check if the given key is one of the user's recovery keys
	 *
	 * @since 1.2
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function is_recovery_key( $key ) {

		$is_key = false;
		$keys   = wpga_get_user_recovery_keys( $this->user_id );

		if ( in_array( md5( sanitize_key( $key ) ), $keys ) ) {
			$is_key = true;
		}

		return $is_key;

	}

	/**
	 * Get the user's app passwords if any
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_app_passwords() {

		if ( is_array( $this->app_passwords ) ) {
			return $this->app_passwords;
		}

		return WPGA()->recovery->get_key_by( 'user_id', $this->user_id, false, 'app_password' );

	}

	/**
	 * Get the actual list of the user's app passwords
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_app_passwords_codes() {

		$passwords = $this->get_app_passwords();
		$codes     = array();

		foreach ( $passwords as $key ) {
			$codes[ $key['ID'] ] = $key['code'];
		}

		return $codes;

	}

	/**
	 * Check if the user has any app passwords set
	 *
	 * @since 1.2
	 * @return bool
	 */
	public function has_app_passwords() {
		return empty( $this->get_app_passwords() ) ? false : true;
	}

}