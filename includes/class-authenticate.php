<?php
/**
 * @package   WP Google Authenticator/Classes/Authenticate
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WPGA_Authenticate {

	/**
	 * Defines if the plugin is active
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	protected $is_2fa_active = null;

	/**
	 * Submitted TOTP
	 *
	 * @since 1.2.0
	 * @var string
	 */
	public $totp;

	/**
	 * The user secret key
	 *
	 * @since 1.2.0
	 * @var string
	 */
	protected $secret;

	public function __construct() {
		add_action( 'wp_authenticate_user',  array( $this, 'authenticate' ), 10, 3 );
		add_filter( 'authenticate',          array( $this, 'checkAppPassword' ), 50, 3 );
	}

	/**
	 * Get the user secret key
	 *
	 * @since 1.2.0
	 *
	 * @param WP_User $user User object
	 *
	 * @return string
	 */
	protected function get_user_secret( $user ) {

		if ( is_null( $this->secret ) ) {
			$this->secret = get_user_meta( $user->ID, 'wpga_secret', true );
		}

		return $this->secret;

	}

	/**
	 * Get the user TOTP
	 *
	 * @since 1.2.0
	 * @return null|string
	 */
	public function get_totp() {

		if ( is_null( $this->totp ) ) {
			$this->totp = isset( $_POST['totp'] ) ? sanitize_key( $_POST['totp'] ) : null;
		}

		return $this->totp;

	}

	/**
	 * Add TOTP check to WordPress authentication process
	 *
	 * @param WP_User|WP_Error $user
	 *
	 * @return object User object on success or WP_Error on failure
	 */
	public function authenticate( $user ) {

		// Well, not much we can do here...
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$totp = $this->get_totp();

		// If no TOTP is found then we continue our process to maybe display the TOTP prompt later
		if ( is_null( $totp ) ) {

			// If the user trying to login has 2FA enabled we need to redirect him to the TOTP prompt
			if ( true === wpga_is_2fa_active( $user ) ) {

				$redirect = add_query_arg( array(
					'action' => '2fa',
					'u'      => $user->data->user_login,
					'_nonce' => wpga_create_nonce_action( $user )
				), wp_login_url() );

				wp_safe_redirect( $redirect );
				die;

			} else {
				return $user;
			}

		} else {

			// Double check that the user has 2FA enabled
			if ( true !== wpga_is_2fa_active( $user ) ) {
				return $user;
			}

			$secret = $this->get_user_secret( $user );

			/* Let's make sure the user has generated a secret */
			if ( '' !== $secret ) {

				if ( is_null( $totp ) ) {
					return new WP_Error( 'no_totp', esc_html__( 'An error is preventing the 2-factor authentication from authenticating your session.', 'wpga' ) );
				}

				if ( empty( $totp ) ) {
					return new WP_Error( 'no_totp', esc_html__( 'Please provide your one time password.', 'wpga' ) );
				}

				$totp_valid = wpga_validate_totp( $secret, $totp );

				if ( is_wp_error( $totp ) ) {
					return $totp_valid;
				}

				// If TOTP is valid we revoke it and continue loggin in
				if ( true === $totp_valid ) {

					// Revoke the TOTP
					wpga_revoke_totp( $totp );

					return $user;

				} /**
				 * Check if the user is sending a recovery key.
				 *
				 * If the recovery key is valid, we deactivate
				 * 2FA for this user so that he can log-in
				 * without using the app.
				 *
				 * @since 1.0.4
				 */
				elseif ( wpga_check_recovery_key( $user, $totp ) ) {

					// Disable 2FA for this user
					wpga_disable_2fa( $user->ID );

					/* Add URL var to the login redirect */
					add_filter( 'login_redirect', 'wpga_login_redirect_notify' );

					return $user;

				} else {
					return new WP_Error( 'totp_invalid', esc_html__( 'The one time password is incorrect or expired. Please try with a newly generated password.', 'wpga' ) );
				}

			} else {

				/* TOTP is forced for all users */
				if ( wpga_is_2fa_forced( $user->roles ) ) {

					$remaining_attempts = wpas_get_remaining_login_attempts( $user->ID );

					/* If the admin set the max attempts to unlimited we give up on security :( */
					if ( - 1 === $remaining_attempts ) {
						return $user;
					}

					if ( $remaining_attempts > 0 ) {
						wpas_increment_attempts( $user->ID );

						return $user;
					} else {
						return new WP_Error( '2fa_max_attempts', esc_html__( 'You have reached the maximum number of logins WITHOUT using 2-factor authentication. Please contact the admin to reset your account.', 'wpga' ) );
					}

				} else {
					// No TOTP check? Just return the user for standard authentication
					return $user;
				}

			}

		}

	}

	/**
	 * Check for app password.
	 *
	 * If the user has created one or more apps passwords,
	 * we check if the given password is a registered one.
	 *
	 * @since  1.1.0
	 *
	 * @param WP_User $user     The user object
	 * @param string  $username The username to authenticate
	 * @param string  $password The user password
	 *
	 * @return WP_User A user object
	 */
	public function checkAppPassword( $user, $username, $password ) {

		if ( ! is_wp_error( $user ) ) {
			return $user;
		}

		$user_data = get_user_by( 'login', $username );

		if ( ! is_object( $user_data ) ) {
			return false;
		}

		if ( $this->has_app_passwords( $user_data->ID ) ) {

			$passwords = wpga_get_app_passwords( $user_data->ID );
			$hash      = md5( $password );
			$key       = wpga_make_unique_key( $hash );

			if ( array_key_exists( $key, $passwords ) ) {

				/* App password is correct. */
				if ( wp_check_password( trim( $password ), $passwords[ $key ]['hash'] ) ) {

					$new   = wpga_get_app_passwords_log( $user_data->ID );
					$count = count( $new );
					$last  = null;

					/* Delete the oldest entry if the limit is reached */
					if ( $count === apply_filters( 'wpga_apps_passwords_log_max', 50 ) ) {
						foreach ( $new as $date => $data ) {
							$last = $date;
						}
						unset( $new[ $last ] );
					}

					$time  = strtotime( 'now' );
					$entry = array(
						'key'        => $key,
						'last_used'  => $time,
						'ip'         => $_SERVER['REMOTE_ADDR'],
						'user_agent' => $_SERVER['HTTP_USER_AGENT'],
						'method'     => '',
					);

					/* Update the password use count */
					$passwords[$key]['count'] = intval( $passwords[$key]['count'] ) + 1;
					update_user_meta( $user_data->ID, 'wpga_apps_passwords', $passwords );

					/* Save the log entry */
					$new[$time] = $entry;
					update_user_meta( $user_data->ID, 'wpga_apps_passwords_log', $new );

					return new WP_User( $user_data->ID );

				} else {
					return new WP_Error( 'wrong_app_password', esc_html__( 'The application password you provided is invalid.', 'wpga' ) );
				}
			} else {
				return new WP_Error( 'no_totp', esc_html__( 'Please provide your one time password.', 'wpga' ) );
			}
		} else {
			return $user;
		}
	}

	/**
	 * Check if the current user has app passwords.
	 *
	 * @since  1.1.0
	 *
	 * @param int $user_id A user ID
	 *
	 * @return boolean True if has app passwords
	 */
	public function has_app_passwords( $user_id ) {

		$passwords = wpga_get_app_passwords( $user_id );

		if ( empty( $passwords ) ) {
			return false;
		} else {
			return true;
		}

	}

}

/**
 * Add a URL var to login redirect page
 *
 * @return string Redirect URL
 * @since 1.0.4
 */
function wpga_login_redirect_notify() {
	return add_query_arg( array( '2fa_reset' => 'true' ), admin_url() );
}

/**
 * Create a security nonce to validate the TOTP prompt
 *
 * @since 1.2.0
 *
 * @param WP_User $user The user object
 *
 * @return string
 */
function wpga_create_nonce_action( $user ) {

	// Set the nonce lifetime to 1 minute for more security
	add_filter( 'nonce_life', 'wpga_alter_nonce_lifetime' );

	return wp_create_nonce( wp_hash( "wpga_$user->ID" ) );

}

/**
 * Check the validity of our security nonce
 *
 * @since 1.2.0
 *
 * @param WP_User $user  User object
 * @param string  $nonce Nonce to validate
 *
 * @return bool
 */
function wpga_validate_nonce( $user, $nonce ) {

	// Set the nonce lifetime to 1 minute for more security
	add_filter( 'nonce_life', 'wpga_alter_nonce_lifetime' );

	if ( $nonce === wpga_create_nonce_action( $user ) ) {
		return true;
	}

	return false;

}

/**
 * Change the nonce lifetime to 60 seconds
 *
 * @since 1.2.0
 * @return int
 */
function wpga_alter_nonce_lifetime() {
	return 120;
}