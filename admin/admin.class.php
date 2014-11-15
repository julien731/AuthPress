<?php
/**
 * Google Authenticator for WordPress
 *
 * This class uses the Google Authenticator algorithm
 * to authenticate WordPress users with 2-factor authentication.
 *
 * @author Julien Liabeuf <julien@liabeuf.fr>
 * @package Google Authenticator for WordPress
 * 
 * TODO
 *
 * - Revoke all users at once
 * - How to block brute force? Authorized desynchronization at -1 for current IP?
 */
class WPGA_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.7
	 * @var      object
	 */
	protected static $instance = null;

	public function __construct() {

		$this->settings      = array();
		$this->apps_settings = array();
		$this->key_length    = apply_filters( 'wpga_secret_key_length', 16 );
		$this->codelength    = apply_filters( 'wpga_code_length', 6 );
		$this->qr_height     = 300;
		$this->qr_width      = 300;
		$this->def_attempt   = 3;
		$this->bkp_length    = apply_filters( 'wpga_recovery_code_length', 24 );
		$this->log_max       = apply_filters( 'wpga_apps_passwords_log_max', 50 );

		if( is_admin() ) {

			add_action( 'wp_ajax_wpga_get_recovery', array( $this, 'ajax_callback' ) );
			add_action( 'wp_ajax_wpga_create_app_password', array( $this, 'create_app_password' ) );

			if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {

				if( isset( $_GET['action'] ) ) {
					add_action( 'init', array( $this, 'EditSecret' ) );
				}
				
				add_action( 'init',                    array( $this, 'initSettings' ) );
				add_action( 'init',                    array( $this, 'registerSettings' ) );
				add_action( 'admin_menu',              array( $this, 'add_app_password_menu' ) );
				add_action( 'admin_notices',           array( $this, 'adminNotices' ) );
				add_action( 'admin_notices',           array( $this, 'ForceSetSecret' ) );
				add_action( 'show_user_profile',       array( $this, 'addUserProfileFields' ) );
				add_action( 'edit_user_profile',       array( $this, 'UserAdminCustomProfileFields' ) );
				add_action( 'personal_options_update', array( $this, 'SaveCustomProfileFields' ) );
				add_action( 'admin_print_scripts',     array( $this, 'load_admin_scripts' ) );

				if( isset( $_GET['page'] ) && $_GET['page'] == 'wpga_options' ) {
					add_filter( 'contextual_help', array( $this, 'help' ), 10, 3 );
				}

				if( isset( $_GET['page'] ) && ( 'wpga_options' == $_GET['page'] ) ) {
					add_filter( 'admin_footer_text', array( $this, 'versionInFooter' ) );
				}

			}
		}

		add_action( 'init',                  array( $this, 'load_plugin_textdomain' ), 9 );
		add_action( 'login_enqueue_scripts', array( $this, 'loadResources' ) );
		add_action( 'admin_print_scripts',   array( $this, 'loadResources' ) );
		add_action( 'login_form',            array( $this, 'customizeLoginForm' ) );
		add_action( 'wp_authenticate_user',  array( $this, 'authenticateUser' ), 10, 3 );
		add_filter( 'authenticate',          array( $this, 'checkAppPassword' ), 50, 3 );
		add_action( 'wpas_clean_totps',      array( $this, 'clean_totps' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.7
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Instanciate the settings class
	 */
	public function initSettings() {

		if( !class_exists( 'TAV_Settings' ) )
			return;

		/* Prepare arguments */
		$args = array(
			'name'       => WPGA_PREFIX . '_options',
			'menu_name'  => __( 'Authenticator', 'wpga' ),
			'parent'     => 'options-general.php',
			'page_title' => __( 'WP Google Authenticator Settings', 'wpga' ),
			'slug'       => WPGA_PREFIX . '_options',
			'page'       => 'wpga-settings',
			'prefix'     => WPGA_PREFIX,
			'row_name'   => WPGA_PREFIX . '_options'
		);

		/* Instanciate the options class */
		$this->settings = new TAV_Settings( $args );
		
	}

	/**
	 * Add required menu items
	 */
	public function add_app_password_menu() {
		add_users_page(
			__( 'Google Authenticator Applications Passwords', 'wpga' ),
			__( 'My Apps Passwords', 'wpga' ),
			'read',
			WPGA_PREFIX . '_apps_passwords',
			'wpga_apps_passwords_display'
		);		
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.3
	 */
	public function load_plugin_textdomain() {

		$domain = WPGA_PREFIX;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		$textdomain = load_textdomain( $domain, WPGA_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );

	}


	/**
	 * Load the scripts resources on the login page used for the tooltip
	 */
	public function loadResources() {

		global $pagenow;

		if( in_array( $pagenow, array( 'wp-login.php', 'users.php' ) ) ) {
			wp_enqueue_script( 'wpga-powertip', WPGA_URL . 'vendor/powertip/jquery.powertip.min.js', array( 'jquery' ), null, true );
			wp_enqueue_script( 'wpga-main', WPGA_URL . 'js/main.js', array(), WPGA_VERSION, true );
			wp_enqueue_style( 'wpga-powertip', WPGA_URL . 'vendor/powertip/jquery.powertip.min.css', array(), null, 'all' );
		}

	}

	/**
	 * Load the plugin custom JS
	 *
	 * @since 1.0.4
	 * @return (void)
	 */
	public function load_admin_scripts() {

		global $pagenow;

		if( 'profile.php' === $pagenow || isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'wpga_options', 'wpga_apps_passwords' ) ) ) {
			wp_enqueue_script( 'wpga-custom', WPGA_URL . 'js/custom.js', array(), WPGA_VERSION, true );
		}
	}

	/**
	 * Register plugin settings that will be displayed
	 * in the WP backend.
	 */
	public function registerSettings() {

		$this->settings->addSection( 'general', 'general' );
		$this->settings->addSection( 'security', 'security' );

		$this->settings->addOption( 'general', array(
			'id' 		=> 'active',
			'title' 	=> __( 'Activate Plugin', 'wpga' ),
			'desc' 		=> __( 'Do you wish to enable the 2-factor authentication for this site?', 'wpga' ),
			'field' 	=> 'checkbox',
			'opts' 		=> array( 'yes' => __( 'Yes', 'wpga' ) )
			)
		);

		$this->settings->addOption( 'general', array(
			'id' 		=> 'force_2fa',
			'title' 	=> __( 'Force Use', 'wpga' ),
			'desc' 		=> __( 'Do you want to force your users to use 2-factor authentication (admins AND you included)?', 'wpga' ),
			'field' 	=> 'checkbox',
			'opts' 		=> array( 'yes' => __( 'Yes', 'wpga' ) )
			)
		);

		$this->settings->addOption( 'general', array(
			'id' 		=> 'user_roles',
			'title' 	=> __( 'Force Roles', 'wpga' ),
			'desc' 		=> __( 'You can force users to use 2-factor authentication by role. Requires &laquo;Force Use&raquo; to be enabled. If no role is checked, 2FA will be forced for ALL roles.', 'wpga' ),
			'field' 	=> 'user_roles',
			'opts' 		=> $this->get_editable_roles()
			)
		);

		$this->settings->addOption( 'general', array(
			'id' 		=> 'blog_name',
			'title' 	=> __( 'Site Name', 'wpga' ),
			'desc' 		=> __( 'Name under which this site will appear in the Google Authenticator app.', 'wpga' ),
			'field' 	=> 'text'
			)
		);

		$this->settings->addOption( 'security', array(
			'id' 		=> 'max_attempts',
			'title' 	=> __( 'Max Attempts', 'wpga' ),
			'desc' 		=> __( 'If you chose to force users to use 2-factor authentication, you can specify a maximum number of times a user can login WITHOUT setting up the 2-factor authentication (leave <code>0</code> for unlimited attempts).', 'wpga' ),
			'field' 	=> 'smalltext'
			)
		);

		$this->settings->addOption( 'security', array(
			'id' 		=> 'authorized_delay',
			'title' 	=> __( 'Authorized Clock Desynchronization', 'wpga' ),
			'desc' 		=> __( 'Must be in <code>min</code> (&plusmn;). Avoid invalid one-time passwords issues. Please read the contextual help for more info.', 'wpga' ),
			'field' 	=> 'smalltext'
			)
		);

	}

	/**
	 * Get roles list.
	 *
	 * @since  1.0.9
	 * @return array List of editable roles
	 */
	public function get_editable_roles() {
		global $wp_roles;

		$all_roles      = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);
		$list           = array();

		foreach ( $editable_roles as $role_id => $role ) {
			$list[$role_id] = $role['name'];
		}

		return $list;
	}

	/**
	 * Register the contextual help for the plugin admin screen
	 */
	public function help() {

		if( !isset( $_GET['page'] ) || $_GET['page'] != 'wpga_options' )
			return;

		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'desynchronization',
			'title'   => __( 'Desynchronization', 'wpga' ),
			'content' => __('<h2>Authorized Clock Desynchronization</h2><p>First of all, you have to understand how the 2-factor authentication works.</p><p>The Google Authenticator will generate a TOTP which stands for Time based One Time Pasword. This one time password, as you might now understand, is generated based on the current time.</p><p>If the server\'s (where your site is hosted) clock and the user\'s phone clock are not perfectly synchronized, the one time password generated won\'t work, as it will be generated on a time which is different from the server.</p><p>The authorized desynchronization will allow your users more time to use their one time password. By default, one code will be valid for <strong>30 seconds</strong>. If you want to give them more time, you can specify a delay in <strong>minutes</strong>.</p><p>Of course, if you give users more time, the security will be lowered. It is advised to stick with the default 30 secs.</p>', 'wpga')
			)
		);

	}

	/**
	 * Edit secret key
	 *
	 * This function will process various actions on the user's
	 * secret key such as regenerate or revoke it. All actions
	 * are checked against a nonce before doing anything.
	 */
	public function EditSecret() {

		switch( $_GET['action'] ):

			case 'regenerate':

				if( !wp_verify_nonce( $_GET['_wpnonce'], 'regenerate_key' ) )
					return;

				$secret = $this->generateSecretKey();
				update_user_meta( get_current_user_id(), 'wpga_secret', $secret );
				wp_redirect( add_query_arg( array( 'update' => '10' ), admin_url( 'profile.php#wpga' ) ) );
				exit;

			break;

			case 'revoke':

				if( !isset( $_GET['user_id'] ) )
					return;

				if( !wp_verify_nonce( $_GET['_wpnonce'], 'revoke_key' ) )
					return;

				delete_user_meta( $_GET['user_id'], 'wpga_secret' );
				wp_redirect( add_query_arg( array( 'user_id' => $_GET['user_id'], 'update' => '11' ), admin_url( 'user-edit.php' ) ) );
				exit;

			break;

			case 'reset':

				if( !wp_verify_nonce( $_GET['_wpnonce'], 'reset_key' ) )
					return;

				delete_user_meta( $_GET['user_id'], 'wpga_attempts' );
				wp_redirect( add_query_arg( array( 'user_id' => $_GET['user_id'], 'update' => '12' ), admin_url( 'user-edit.php' ) ) );
				exit;

			break;

		endswitch;

	}

	/**
	 * Add admin notices
	 */
	public function adminNotices() {

		if( isset( $_GET['2fa_reset'] ) && 'true' == $_GET['2fa_reset'] ) { ?>

			<div class="error">
				<p><?php printf( __( '2-factor authentication has been deactivated for your account. If you want to reactivate it, go to your %sprofile page%s.', 'wpga' ), '<a href="' . admin_url( 'profile.php' ) . '#wpga">', '</a>' ); ?></p>
			</div>

		<?php }

		if( !isset( $_GET['update'] ) )
			return;

		$uid = isset( $_GET['user_id'] ) ? $_GET['user_id'] : '';

		$messages = array(
			'10' => __( 'Your secret key has been regenerated.', 'wpga' ),
			'11' => sprintf( __( 'The key for user %s has been revoked.', 'wpga' ), $uid ),
			'12' => sprintf( __( 'The attempts count has been reset.', 'wpga' ), $uid ),
		);

		?>
		<div class="updated">
			<p><?php echo $messages[$_GET['update']]; ?></p>
		</div>
		<?php

	}

	/**
	 * Ask user to setup a secret key
	 *
	 * If the admin sets the use of 2-factor authentication,
	 * every user will be reminded to setup a secret key. The
	 * message will only disappear after the user completed
	 * the configuration.
	 */
	public function ForceSetSecret() {

		$user     = wp_get_current_user();
		$active   = $this->settings->getOption( 'active', array() );
		$force    = $this->settings->getOption( 'force_2fa', array() );
		$roles    = $this->settings->getOption( 'user_roles', array() );
		$affected = !empty( $roles ) ? $roles : array( $user->roles[0] );

		if( in_array( 'yes', $active ) && in_array( 'yes', $force ) ) {

			if ( 'all' === $this->settings->getOption( 'user_role_status', 'all' ) || in_array( $user->roles[0], $affected ) ) {

				$secret       = esc_attr( get_the_author_meta( 'wpga_secret', $user->ID ) );
				$max_attempts = (int)$this->settings->getOption( 'max_attempts', $this->def_attempt );
				$attempts     = (int)get_user_meta( $user->ID, 'wpga_attempts', true );
				$left         = $max_attempts-$attempts;
				
				if( '' == $secret ) {

					?>
					<div class="error">
						<p><?php printf( __( 'The admin is requesting all users to activate 2-factor authentication. <a href="%s">Please do it now</a>. You only have <strong>%s</strong> login attempts left.', 'wpga' ), admin_url( 'profile.php#wpga' ), $left ); ?></p>
					</div>
					<?php

				}

			}

		}

	}

	/**
	 * Add version number in footer
	 */
	public function versionInFooter() {

		printf( __( WPGA_NAME . ' version ' . WPGA_VERSION . ' by <a href="%s">' . WPGA_AUTHOR . '</a>.', 'gtsp' ), esc_url( WPGA_URI ) );

	}

	/**
	 * Generate a secret key based on allowed chars
	 * base32 compatible.
	 * @return (string) Secret key
	 */
	public function generateSecretKey() {

		$validChars = $this->getValidChars();

		unset( $validChars[32] );

		$secret = '';

		for( $i = 0; $i < $this->key_length; $i++ ) {

			$secret .= $validChars[array_rand($validChars)];

		}

		return $secret;
	}

	/**
	 * Generate a backup key
	 *
	 * In case the user loses his phone or cannot access the Google Authenticator app,
	 * we generate a unique backup key that the user can use to authenticate once.
	 * After one (only) authentication the key will be voided.
	 * 
	 * @return (string) Backup key
	 * @since 1.0.4
	 */
	public function generate_backup_key() {

		$length = $this->bkp_length;
		$max    = ceil( $length / 40 );
		$random = '';

		for ($i = 0; $i < $max; $i ++) {
			$random .= sha1( microtime( true ) . mt_rand( 10000,90000 ) );
		}

		return substr( $random, 0, $length );
	}

   /**
	* List the base32 valid chars that can be used for
	* secret key generation.
	* 
	* @return (array) Valid chars
	*/
	private function getValidChars() {

		return array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
			'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
			'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
			'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
			'='  // padding char
		);
	}

	/**
	 * Decode base32 string
	 * 
	 * @param  (string) $string String to decode
	 * @return (string) Decoded string
	 */
	function base32_decode( $string ) {

		$lut = array("A" => 0,       "B" => 1,
			"C" => 2,       "D" => 3,
			"E" => 4,       "F" => 5,
			"G" => 6,       "H" => 7,
			"I" => 8,       "J" => 9,
			"K" => 10,      "L" => 11,
			"M" => 12,      "N" => 13,
			"O" => 14,      "P" => 15,
			"Q" => 16,      "R" => 17,
			"S" => 18,      "T" => 19,
			"U" => 20,      "V" => 21,
			"W" => 22,      "X" => 23,
			"Y" => 24,      "Z" => 25,
			"2" => 26,      "3" => 27,
			"4" => 28,      "5" => 29,
			"6" => 30,      "7" => 31
		);

		$string = strtoupper($string);
		$l      = strlen($string);
		$n      = 0;
		$j      = 0;
		$binary = "";

		for ($i = 0; $i < $l; $i++) {

			$n = $n << 5;
			$n = $n + $lut[$string[$i]];       
			$j = $j + 5;

			if ($j >= 8) {
				$j = $j - 8;
				$binary .= chr(($n & (0xFF << $j)) >> $j);
			}
		}

		return $binary;
	}

	/**
     * Calculate the code, with given secret and point in time
     *
     * @param (string) $secret
     * @param (integer) $timeSlice
     * @return (string) Generated code
     */
	public function getCode( $secret, $timeSlice = null ) {

		if( $timeSlice === null ) {

			$timeSlice = floor(time() / 30);

		}

		$secretkey = $this->base32_decode( $secret );

        // Pack time into binary string
		$time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);

        // Hash it with users secret key
		$hm = hash_hmac('SHA1', $time, $secretkey, true);

        // Use last nipple of result as index/offset
		$offset = ord(substr($hm, -1)) & 0x0F;

        // grab 4 bytes of the result
		$hashpart = substr($hm, $offset, 4);

        // Unpak binary value
		$value = unpack('N', $hashpart);
		$value = $value[1];

        // Only 32 bits
		$value = $value & 0x7FFFFFFF;

		$modulo = pow(10, $this->codelength);

		return str_pad($value % $modulo, $this->codelength, '0', STR_PAD_LEFT);
	}

	/**
     * Check if the code is correct. This will accept codes starting from $drift*30sec ago to $drift*30sec from now
     *
     * @package PHPGangsta_GoogleAuthenticator
     * @author Michael Kliewe
     * @param string $secret
     * @param string $code
     * @param int $discrepancy This is the allowed time drift in 30 second units (8 means 4 minutes before or after)
     * @return bool
     */
	public function checkTOTP( $secret, $code ) {

		$options 			= get_option( 'wpga_options' );
		$drift 	 			= isset( $options['authorized_delay'] ) ? (int)$options['authorized_delay']*2 : 1;
		$currentTimeSlice 	= floor( time() / 30 );

		for( $i = -$drift; $i <= $drift; $i++ ) {

			$calculatedCode = $this->getCode( $secret, $currentTimeSlice + $i );

			if( $calculatedCode == $code ) {

				return true;

			}
		}

		return false;
	}

	/**
	 * Check if 2FA is enabled.
	 *
	 * Verifies if the user trying to log in has 2FA enabled.
	 * If not, we check if the site admin did force 2FA and if this
	 * user is affected.
	 * 
	 * @param  object  $user The current user object
	 * @return boolean       True if 2FA is enabled for this user, false otherwise
	 */
	public function is_2fa_enabled( $user ) {

		if ( is_wp_error( $user ) ) {
			return false;
		}

		/* First of all we check if 2FA is enabled for this user. */
		if ( 'yes' === get_user_meta( $user->ID, 'wpga_active', true ) ) {
			return true;
		}

		$options = get_option( 'wpga_options', array() );

		/* Check if 2FA is forced by the admin */
		if ( !isset( $options['force_2fa'] ) || !in_array( 'yes', (array)$options['force_2fa'] )  ) {
			return false;
		}

		if ( 'all' === $options['user_role_status'] ) {
			return true;
		}

		/* If the forced roles list is empty, we consider it active for all users. Hence, we add the current user role in the list. */
		if ( !isset( $options['user_roles'] ) || empty( $options['user_roles'] ) ) {
			$options['user_roles'] = array( $user->roles[0] );
		}

		/* Check if 2FA is forced for the role this user has */
		if ( !in_array( $user->roles[0], $options['user_roles'] ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Add TOTP check to WordPress authentication process
	 * 
	 * @param  (object) $user
	 * @param  (string) $password
	 * @return (object) User object on success or WP_Error on failure
	 */
	public function authenticateUser( $user, $password ) {

		$options = get_option( 'wpga_options', array() );

		if( !isset( $options['active'] ) || !in_array( 'yes', $options['active'] )  )
			return $user;

		/* Get the current user agent */
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		if( !is_wp_error( $user ) ) {

			$username 	= $user->data->user_login;
			$options	= get_option( 'wpga_options', array() );
			$secret 	= get_user_meta( $user->ID, 'wpga_secret', true );
			$active 	= get_user_meta( $user->ID, 'wpga_active', true );
			$totp 		= isset( $_POST['totp'] ) ? sanitize_key( $_POST['totp'] ) : null;

			/* TOTP is forced for all users */
			if ( $this->is_2fa_enabled( $user ) ) {

				/* Let's make sure the user has generated a secret */
				if( '' != $secret ) {

					if ( is_null( $totp ) ) {
						return new WP_Error( 'no_totp', __( 'An error is preventing the 2-factor authentication from authenticating your session.', 'wpga' ) );
					}

					if ( empty( $totp ) ) {
						return new WP_Error( 'no_totp', __( 'Please provide your one time password.', 'wpga' ) );
					}

					if( $this->checkTOTP( $secret, $totp ) ) {

						$used = get_option( 'wpga_used_totp', array() );

						if( is_array( $used ) && !in_array( md5( $totp ), $used ) ) {

							array_push( $used, md5( $totp ) );

							update_option( 'wpga_used_totp', $used );

							return $user;

						} else {
							return new WP_Error( 'expired_totp', __( 'The one time password you used has already been revoked.', 'wpga' ) );
						}

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
					elseif( $this->check_recovery_key( $user, $totp ) ) {

						/* Clean the 2FA data */
						delete_user_meta( $user->ID, 'wpga_active' );
						delete_user_meta( $user->ID, 'wpga_attempts' );
						delete_user_meta( $user->ID, 'wpga_secret' );
						delete_user_meta( $user->ID, 'wpga_backup_key' );
						delete_user_meta( $user->ID, 'wpga_backup_key_time' );

						/* Add URL var to the login redirect */
						add_filter( 'login_redirect', array( $this, 'login_redirect_notify' ) );

						return $user;

					} else {
						return new WP_Error( 'totp_invalid', __( 'The Google Authenticator one time password is incorrect or expired. Please try with a newly generated password.', 'wpga' ) );
					}

				} else {

					$options 		= get_option( 'wpga_options', array() );
					$attempts 		= (int)get_user_meta( $user->ID, 'wpga_attempts', true );
					$max_attempts 	= ( isset( $options['max_attempts'] ) && '' != $options['max_attempts'] ) ? $options['max_attempts'] : $this->def_attempt;

					/* If the admin set the max attempts to unlimited we give us with security :( */
					if ( 0 === $max_attempts ) {
						return $user;
					}

					if( $attempts < $max_attempts ) {
						update_user_meta( $user->ID, 'wpga_attempts', $attempts+1, $attempts );
						return $user;
					} else {
						return new WP_Error( '2fa_max_attempts', __( 'You have reached the maximum number of logins WITHOUT using 2-factor authentication. Please contact the admin to reset your account.', 'wpga' ) );
					}

				}

			}

			/* No TOTP check? Just return the user for standard authentification */
			else {

				return $user;

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
	 */
	public function checkAppPassword( $user, $username, $password ) {

		if ( !is_wp_error( $user ) ) {
			return $user;
		}

		$user_data = get_user_by( 'login', $username );

		if ( !is_object( $user_data ) ) {
			return;
		}

		if ( $this->has_app_passwords( $user_data->ID ) ) {

			$passwords = wpga_get_app_passwords( $user_data->ID );
			$hash      = md5( $password );
			$key       = wpga_make_unique_key( $hash );

			if ( array_key_exists( $key, $passwords ) ) {

				/* App password is correct. */
				if ( wp_check_password( trim( $password ), $passwords[$key]['hash'] ) ) {

					$log   = $new = wpga_get_app_passwords_log( $user_data->ID );
					$count = count( $new );
					$last  = null;

					/* Delete the oldest entry if the limit is reached */
					if ( $count === $this->log_max ) {
						foreach ( $new as $date => $data ) {
							$last = $date;
						}
						unset( $new[$date] );
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
					return new WP_Error( 'wrong_app_password', __( 'The application password you provided is invalid.', 'wpga' ) );
				}
			} else {
				return new WP_Error( 'no_totp', __( 'Please provide your one time password.', 'wpga' ) );
			}
		} else {
			return $user;
		}
	}

	/**
	 * Check if the current user has app passwords.
	 *
	 * @since  1.1.0
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

	/**
	 * Add a URL var to login redirect page
	 * 
	 * @return (string) Redirect URL
	 * @since 1.0.4
	 */
	public function login_redirect_notify() {

		return add_query_arg( array( '2fa_reset' => 'true' ), admin_url() );

	}

	/**
	 * Check validity of a recovery key
	 * 
	 * @param  (object) $user User object
	 * @param  (string) $key  Recovery key to check
	 * @return (boolean)      Whether or not the key is valid
	 * @since  1.0.4
	 */
	public function check_recovery_key( $user, $key ) {

		$recovery = get_user_meta( $user->ID, 'wpga_backup_key', true );

		if( sanitize_key( $key ) == $recovery )
			return true;

		else
			return false;
		
	}

	/**
	 * Get QR Code
	 *
	 * Generate QR code through Google Chart using https
	 * 
	 * @return (string) QR Code URL
	 */
	public function getQRCodeGoogleUrl() {

		$blogname	= rawurlencode( $this->settings->getOption( 'blog_name' ) );
		$secret		= esc_attr( get_the_author_meta( 'wpga_secret', get_current_user_id() ) );
		$account	= ( get_the_author_meta( 'user_login', get_current_user_id() ) );
		$label		= $blogname . ':' . $account;

		$urlencoded = rawurlencode('otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . $blogname );

		return 'https://chart.googleapis.com/chart?chs=' . $this->qr_width . 'x' . $this->qr_height . '&chld=M|0&cht=qr&chl=' . $urlencoded;
	}

	/**
	 * Add profile custom fields
	 * 
	 * @param (integer) $user
	 */
	public function addUserProfileFields( $user ) {

		add_thickbox();
		$force 	= $this->settings->getOption( 'force_2fa' );
		$qr 	= true;
		$width 	= $this->qr_width+10;
		$height	= $this->qr_height+10;
		$secret = esc_attr( get_the_author_meta( 'wpga_secret', $user->ID ) );
		$args 	= array( 'action' => 'regenerate' );
		$backup = get_user_meta( $user->ID, 'wpga_backup_key', true );
		if( isset( $_GET['user_id'] ) ) { $args['user_id'] = $_GET['user_id']; }
		$regenerate = wp_nonce_url( add_query_arg( $args, admin_url( 'profile.php' ) ), 'regenerate_key' );

		if( '' == $secret ) {

			$secret = $this->generateSecretKey();
			$qr 	= false;

		}
		?>

		<h3 id="wpga"><?php _e( 'WP Google Authenticator Settings', 'wpga' ); ?></h3>

		<table class="form-table">

			<?php if( !$force || $force && is_array( $force ) && !in_array( 'yes', $force ) ):

				$active = esc_attr( get_the_author_meta( 'wpga_active', $user->ID ) );

				if( 'yes' == $active ) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				} ?>

				<tr>
					<th><label for="wpga_active"><?php _e( 'Activate', 'wpga' ); ?></label></th>
					<td>
						<input type="checkbox" name="wpga_active" id="wpga_active" value="yes" <?php echo $checked; ?> /><br />
						<p class="description"><?php _e( 'Do you wish to use 2-factor authentication (require the Google Authenticator app)?', 'wpga' ); ?></p>
					</td>
				</tr>

			<?php endif; ?>

			<tr>
				<th><label for="wpga_secret"><?php _e( 'Secret', 'wpga' ); ?></label></th>
				<td>
					<?php if( !$qr ): ?>
						<input type="hidden" name="wpga_secret" id="wpga_secret" value="<?php echo $secret; ?>" />
						<button type="submit" class="button button-secondary"><?php _e( 'Generate Key', 'wpga' ); ?></button>
						<p class="description"><?php _e( 'This is going to be your secret key. Please save changes and scroll back to this field to get your QR code.', 'wpga' ); ?></p>
					<?php else: ?>
						<input type="text" name="wpga_secret" id="wpga_secret" value="<?php echo $secret; ?>" disabled="disabled" /> 
						<input type="hidden" name="wpga_secret" id="wpga_secret" value="<?php echo $secret; ?>" /> 
						<a href="#TB_inline?width=<?php echo $width; ?>&height=<?php echo $height; ?>&inlineId=wpga-qr-code" class="thickbox button button-secondary"><?php _e( 'Get QR Code', 'wpga' ); ?></a> 
						<a href="<?php echo $regenerate; ?>" class="button button-secondary"><?php _e( 'Regenerate Key', 'wpga' ); ?></a>
						<p class="description"><?php _e( 'This is your personal secret key. Don\'t share it!', 'wpga' ); ?></p>
					<?php endif; ?>
					<div id="wpga-qr-code" style="display:none;">
						 <img src="<?php echo $this->getQRCodeGoogleUrl(); ?>" alt="<?php _e( 'QR Code', 'wpga' ); ?>">
					</div>
				</td>
			</tr>

			<?php if( '' != $backup ):

				$time  = get_user_meta( $user->ID, 'wpga_backup_key_time', true );
				$limit = $time + 300; // Recovery key generation time + 5 mins
				?>
				<tr id="wpga-recovery-field">
					<th><label for="wpga_active"><?php _e( 'Recovery Code', 'wpga' ); ?></label></th>
					<td>

						<?php
						/**
						 * After it was generated, the rescue code
						 * will be displayed for 5 minutes. After that,
						 * the user will need to type his password
						 * to reveal the rescue code.
						 */
						if( time() <= $limit ): ?>

							<div style='font-size:18px; font-weight: bold;'><?php echo $backup; ?></div><p><?php _e( 'Write this down and keep it safe', 'wpga' ); ?></p>

						<?php else: ?>

							<p class="wpga-check-pwd-link"><a href="#" class="wpga-check-password"><?php _e( 'Show', 'wpga' ); ?></a></p>

							<div id="wpga-recovery" style="display:none;">
								<p><?php _e( 'For security reasons, please type your password to see your recovery code.', 'wpga' ); ?></p>
								<input type="password" name="pwd" id="pwd">
								<input type="submit" value="OK" placeholder="<?php _e( 'Account password', 'wpga' ); ?>" class="button button-secondary wpga-show-recovery">
								<p class="description"><?php _e( 'If you are unable to use the Google Authenticator for any reason, you can use this one time recovery code instead of the TOTP. Save this code in a safe place.', 'wpga' ); ?></p>
							</div>

						<?php endif; ?>

					</td>
				</tr>
			<?php endif; ?>

		</table>
	<?php }

	/**
	 * Get recovery code
	 *
	 * The function will check the user's password and,
	 * if the password is correct, it will return
	 * the recovery code.
	 *
	 * @return (void)
	 * @since 1.0.4
	 */
	public function ajax_callback() {

		if( !isset( $_POST['pwd'] ) )
			return false;

		/* Password to check */
		$pwd = sanitize_text_field( $_POST['pwd'] );

		$user_id = get_current_user_id();
		$user    = get_user_by( 'id', $user_id );

		if ( $user && wp_check_password( $pwd, $user->data->user_pass, $user->ID ) ) {

			$recovery = get_user_meta( $user_id, 'wpga_backup_key', true );

			if( '' != $recovery )
				echo "<div style='font-size:18px; font-weight: bold;'>$recovery</div><p>" . _e( 'Write this down and keep it safe', 'wpga' ) . "</p>";
			else
				_e( 'No recovery code set yet.', 'wpga' );

		} else {
			?><strong><?php _e( 'Wrong password', 'wpga' ); ?></strong><?php
		}

		die();

	}

	/**
	 * Create a new app password.
	 *
	 * @since  1.1.0
	 */
	public function create_app_password() {

		if ( !isset( $_POST['description'] ) || empty( $_POST['description'] ) ) {
			die();
		}

		global $current_user;

		$passwords = $new = is_array( $p = get_user_meta( $current_user->ID, 'wpga_apps_passwords', true ) ) ? $p : array();
		$pwd       = $this->generate_backup_key();
		$hash      = md5( esc_attr( $pwd ) );
		$key       = wpga_make_unique_key( $hash );
		$return    = json_encode( array( 'desc' => sanitize_text_field( $_POST['description'] ), 'pwd' => esc_attr( $pwd ) ) );
		$new[$key] = array( 'description' => sanitize_text_field( $_POST['description'] ), 'hash' => $hash, 'count' => 0 );

		update_user_meta( $current_user->ID, 'wpga_apps_passwords', $new, $passwords );

		echo esc_attr( urlencode( $return ) );
		die();
	}

	/**
	 * Add admin control fields in user profile
	 */
	public function UserAdminCustomProfileFields() {

		if( !current_user_can( 'administrator' ) || !isset( $_GET['user_id'] ) )
			return;

		$options      = get_option( 'wpga_options', array() );
		$secret       = esc_attr( get_the_author_meta( 'wpga_secret', $_GET['user_id'] ) );
		$args         = array( 'action' => 'revoke', 'user_id' => $_GET['user_id'] );
		$rst_arg      = array( 'action' => 'reset', 'user_id' => $_GET['user_id'] );
		$revoke       = wp_nonce_url( add_query_arg( $args, admin_url( 'user-edit.php' ) ), 'revoke_key' );
		$rst          = wp_nonce_url( add_query_arg( $rst_arg, admin_url( 'user-edit.php' ) ), 'reset_key' );
		$attempts     = (int)get_user_meta( $_GET['user_id'], 'wpga_attempts', true );
		$max_attempts = ( isset( $options['max_attempts'] ) && '' != $options['max_attempts'] ) ? (int)$options['max_attempts'] : $this->def_attempt;
		?>
		<h3><?php _e( 'WP Google Authenticator Settings', 'wpga' ); ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="wpga_secret"><?php _e( 'Secret', 'wpga' ); ?></label></th>
				<td>
					<?php if( '' == $secret ): ?>
						<p><strong><?php _e( 'This user didn\'t set a secret key.', 'wpga' ); ?></strong></p>
					<?php else: ?>
						<p><strong><?php _e( 'This user has a secret key.', 'wpga' ); ?></strong> <a href="<?php echo $revoke; ?>" class="button button-secondary"><?php _e( 'Revoke Key', 'wpga' ); ?></a></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="wpga_attempts"><?php _e( 'Login Attempts', 'wpga' ); ?></label></th>
				<td>
					<input type="text" name="wpga_attempts" id="wpga_attempts" value="<?php echo $attempts; ?>" class="small-text" disabled="disabled" /> 
					<a href="<?php echo $rst; ?>" class="button button-secondary"><?php _e( 'Reset', 'wpga' ); ?></a> 
					<?php if( $max_attempts != 0 && $attempts > $max_attempts ) { echo '<span style="color: red;"><strong>' . __( '(This user is locked out)', 'wpga' ) . '</strong></span>'; } ?>
					<p class="description"><?php _e( 'Number of times the user logged-in without using the TOTP.', 'wpga' ); ?></p>
				</td>
			</tr>

		</table>
		<?php

	}

	/**
	 * Save custom profile fields
	 * @param (integer) $user_id User ID
	 */
	public function SaveCustomProfileFields( $user_id ) {

		if( !current_user_can( 'edit_user', $user_id ) )
			return false;

		if( '' == get_user_meta( $user_id, 'wpga_secret', true ) ) {

			update_user_meta( $user_id, 'wpga_active', 'yes' );

		} else {

			if( isset( $_POST['wpga_active'] ) ) {
				update_user_meta( $user_id, 'wpga_active', $_POST['wpga_active'] );
			} else {
				delete_user_meta( $user_id, 'wpga_active' );
			}

		}

		update_user_meta( $user_id, 'wpga_secret', $_POST['wpga_secret'] );

		/**
		 * Delete the user login attempts without using 2FA.
		 * This avoids an incorrect number of allowed attempts
		 * in case the user deactivates the 2FA for his account.
		 *
		 * @since  1.0.8
		 */
		delete_user_meta( $user_id, 'wpga_attempts' );

		/* Check if backup key exist */
		$backup = get_user_meta( $user_id, 'wpga_backup_key', true );

		if( '' == $backup ) {

			/* Generate a new backup key */
			$key = $this->generate_backup_key();

			/* Save the backup key */
			update_user_meta( $user_id, 'wpga_backup_key', sanitize_key( $key ) );

			/**
			 * Set a session var to allow user seeing the backup key
			 * without having to enter his password. This will only happen once
			 */
			update_user_meta( $user_id, 'wpga_backup_key_time', time() );

		}
	}

	/**
	 * Add verification code field to login form.
	 */
	public function customizeLoginForm() {

		$options = get_option( 'wpga_options', array() );

		if( !isset( $options['active'] ) || !in_array( 'yes', $options['active'] )  )
			return;

		?>
		<p>
			<label for="authenticator">
				<?php _e( 'Google Authenticator', 'wpga' ); ?> <small><a href="#" title="<?php _e( 'If you do not have configured the 2-factor authentication,<br> just leave this field blank and you will be logged-in as usual.<br><br>If you can\'t use the Google Authenticator app for whatever reason,<br>you can use your recovery code instead.', 'wpga' ); ?>" class="wpgahelp" tabindex="-1">[?]</a></small>
				<br>
				<input id="authenticator" class="input" type="text" size="20" value="" name="totp">
			</label>
		</p>
		<?php
	}

	/**
	 * Delete all TOTPs from DB.
	 *
	 * As TOTPs expire after a defined amount of time
	 * per definition, there is no need to store them
	 * in the database forever.
	 *
	 * @since 1.0.7
	 */
	public function clean_totps() {
		delete_option( 'wpga_used_totp' );
	}

}

/**
 * Display the applications passwords apge.
 * 
 * @since 1.1.0
 */
function wpga_apps_passwords_display() {
	require_once( WPGA_PATH . 'admin/views/apps-passwords.php' );
}

add_action( 'admin_init', 'wpas_apps_passwords_actions' );
/**
 * Run app passwords related actions.
 *
 * Run the actions and redirect to the user's page
 * in "read only" mode, without the URL vars that can cause
 * undesired actions (like clearing the log again).
 *
 * @since  1.1.0
 * @return void
 */
function wpas_apps_passwords_actions() {

	if ( isset( $_GET['action'] ) && isset( $_GET['wpga_nonce'] ) ) {

		if ( wp_verify_nonce( $_GET['wpga_nonce'], 'wpga_action' ) ) {

			switch ( $_GET['action'] ) {
				case 'delete':
				
					if ( isset( $_GET['key'] ) ) {
						$delete_key = sanitize_key( $_GET['key'] );
						wpga_delete_app_password( $delete_key );
					}

				break;

				case 'delete_all':
					wpga_reset_app_passwords();
				break;

				case 'clear_log':
					wpga_clear_log();
				break;
				
			}

		}

		wp_redirect( add_query_arg( array( 'page' => 'wpga_apps_passwords' ), admin_url( 'users.php') ) );
		exit;

	}

}