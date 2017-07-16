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

	/**
	 * The user object
	 *
	 * @since 1.2
	 * @var WPGA_User
	 */
	protected $user;

	public function __construct() {
		add_action( 'wp_authenticate_user',  array( $this, 'authenticate' ), 10, 3 );
		add_filter( 'authenticate',          array( $this, 'checkAppPassword' ), 50, 3 );
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
	 * @param  WP_User|WP_Error $user
	 *
	 * @return object User object on success or WP_Error on failure
	 */
	public function authenticate( $user ) {

		if ( true !== wpga_is_2fa_active( $user ) ) {
			return $user;
		}

		if ( ! is_wp_error( $user ) ) {

			// Instantiate our user class for easy access to user data
			if ( is_null( $this->user ) ) {
				$this->user = new WPGA_User( $user );
			}

			$secret = $this->user->get_secret();
			$totp   = $this->get_totp();

			/* Let's make sure the user has generated a secret */
			if ( '' !== $secret ) {

				if ( is_null( $totp ) ) {
					return new WP_Error( 'no_totp', esc_html__( 'An error is preventing the 2-factor authentication from authenticating your session.', 'wpga' ) );
				}

				if ( empty( $totp ) ) {
					return new WP_Error( 'no_totp', esc_html__( 'Please provide your one time password.', 'wpga' ) );
				}

				$totp_valid = $this->user->is_otp_valid( $totp );

				if ( is_wp_error( $totp_valid ) ) {
					return $totp_valid;
				}

				// If TOTP is valid we revoke it and continue loggin in
				if ( true === $totp_valid ) {

					// Revoke the TOTP
					wpga_revoke_totp( $totp );

					return $user;

				}

				/**
				 * Check if the user is sending a recovery key.
				 *
				 * If the recovery key is valid, we deactivate
				 * 2FA for this user so that he can log-in
				 * without using the app.
				 *
				 * @since 1.0.4
				 */
				elseif ( $this->user->is_recovery_key( $totp ) ) {

					// Disable 2FA for this user
					$this->user->deactivate_2fa();

					/* Add URL var to the login redirect */
					add_filter( 'login_redirect', 'wpga_login_redirect_notify' );

					return $user;

				} else {
					return new WP_Error( 'totp_invalid', esc_html__( 'The one time password is incorrect or expired. Please try with a newly generated password.', 'wpga' ) );
				}

			} else {

				/* TOTP is forced for all users */
				if ( wpga_is_2fa_forced( $user->roles ) ) {

					/* If the admin set the max attempts to unlimited we give up on security :( */
					if ( - 1 === $this->user->remaining_attempts() ) {
						return $user;
					}

					if ( $this->user->remaining_attempts() > 0 ) {

						$this->user->add_attempt();

						return $user;
					} else {
						return new WP_Error( '2fa_max_attempts', esc_html__( 'You have reached the maximum number of logins WITHOUT using 2-factor authentication. Please contact the admin to reset your account.', 'wpga' ) );
					}

				} else {
					// No TOTP check? Just return the user for standard authentication
					return $user;
				}

			}

		} else {
			return $user;
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
	 * @return null|WP_User|WP_Error A user object on success or an error message
	 */
	public function checkAppPassword( $user, $username, $password ) {

		// Only do our work if the authentication failed.
		if ( ! is_wp_error( $user ) ) {
			return $user;
		}

		$user_data = get_user_by( 'login', $username );

		if ( ! is_object( $user_data ) || ! is_a( $user_data, 'WP_User' ) ) {
			return null;
		}

		// Get the WPGA user object
		$wpga_user = new WPGA_User( $user_data );

		if ( $wpga_user->has_app_passwords() ) {

			$passwords = $wpga_user->get_app_passwords_codes(); // Get all user app passwords
			$hash      = md5( $password ); // Hash the given supposed app password

			if ( in_array( $hash, $passwords ) ) {

				$pwd_keys = array_flip( $passwords );

				WPGA()->access_log->log_access( $user_data->ID, $pwd_keys[ $hash ], current_time( 'mysql' ), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );

				// INCREMENT COUNT

				return new WP_User( $user_data->ID );

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