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

	public function __construct() {

		$this->settings 	= array();
		$this->key_length 	= 16;
		$this->codelength 	= 6;
		$this->qr_height 	= 300;
		$this->qr_width 	= 300;
		$this->def_attempt  = 3;

		if( is_admin() ) {

			add_action( 'init', array( $this, 'initSettings' ) );
			add_action( 'init', array( $this, 'registerSettings' ) );
			add_action( 'init', array( $this, 'EditSecret' ) );
			add_action( 'admin_notices', array( $this, 'adminNotices' ) );
			add_action( 'admin_notices', array( $this, 'ForceSetSecret' ) );
			add_action( 'show_user_profile', array( $this, 'addUserProfileFields' ) );
			add_action( 'edit_user_profile', array( $this, 'UserAdminCustomProfileFields' ) );
			add_action( 'personal_options_update', array( $this, 'SaveCustomProfileFields' ) );
			add_filter( 'contextual_help', array( $this, 'help' ), 10, 3 );

			if( isset( $_GET['page'] ) && ( 'wpga_options' == $_GET['page'] ) )
				add_filter( 'admin_footer_text', array( $this, 'versionInFooter' ) );
		}

		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 9 );
		add_action( 'login_enqueue_scripts', array( $this, 'loadResources' ) );
		add_action( 'login_form', array( $this, 'customizeLoginForm' ) );
		add_action( 'wp_authenticate_user', array( $this, 'authenticateUser' ), 10, 3 );

	}

	/**
	 * Instanciate the settings class
	 */
	public function initSettings() {

		if( !class_exists( 'TAV_Settings' ) )
			return;

		/* Prepare arguments */
		$args = array(
			'name' 			=> WPGA_PREFIX . '_options',
			'menu_name' 	=> __( 'Authenticator', 'wpga' ),
			'parent' 		=> 'options-general.php',
			'page_title' 	=> __( 'WP Google Authenticator Settings', 'wpga' ),
			'slug' 			=> WPGA_PREFIX . '_options',
			'page' 			=> 'wpga-settings',
			'prefix' 		=> WPGA_PREFIX,
			'row_name' 		=> WPGA_PREFIX . '_options'
		);

		/* Instanciate the options class */
		$this->settings = new TAV_Settings( $args );
		
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

		if( 'wp-login.php' == $pagenow ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wpga-powertip', WPGA_URL . 'vendor/powertip/jquery.powertip.min.js', array(), null, true );
			wp_enqueue_script( 'wpga-main', WPGA_URL . 'js/main.js', array(), null, true );
			wp_enqueue_style( 'wpga-powertip', WPGA_URL . 'vendor/powertip/jquery.powertip.min.css', array(), null, 'all' );
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

		if( !isset( $_GET['action'] ) )
			return;

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

		$force = $this->settings->getOption( 'force_2fa' );

		if( $force && is_array( $force ) && in_array( 'yes', $force ) ) {

			$user 			= wp_get_current_user();
			$secret 		= esc_attr( get_the_author_meta( 'wpga_secret', $user->ID ) );
			$max_attempts 	= (int)$this->settings->getOption( 'max_attempts', $this->def_attempt );
			$attempts 		= (int)get_user_meta( $user->ID, 'wpga_attempts', true );
			$left 			= $max_attempts-$attempts;
			
			if( '' == $secret ) {

				?>
				<div class="error">
					<p><?php printf( __( 'The admin is requesting all users to activate 2-factor authentication. <a href="%s">Please do it now</a>. You only have <strong>%s</strong> login attempts left.', 'wpga' ), admin_url( 'profile.php#wpga' ), $left ); ?></p>
				</div>
				<?php

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

		/**
		 * We cannot add support to the WordPress Android / iPhone app,
		 * so we deactivate the 2-factor authentication in this case,
		 * even if it lowers security. No choice for now.
		 *
		 * @since 1.0.3
		 */
		$excludes = array(
			'wp-iphone',
			'wp-android'
		);

		/* Get the current user agent */
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		foreach( $excludes as $exclude ) {

			/* If the user agent matches a WordPress app we abort */
			if( strpos( $user_agent, $exclude ) !== false )
				return $user;

		}

		if( !is_wp_error( $user ) ) {

			$username 	= $user->data->user_login;
			$options	= get_option( 'wpga_options', array() );
			$secret 	= get_user_meta( $user->ID, 'wpga_secret', true );
			$active 	= get_user_meta( $user->ID, 'wpga_active', true );
			$totp 		= sanitize_key( $_POST['totp'] );

			/* TOTP is forced for all users */
			if( ( isset( $options['force_2fa'] ) && is_array( $options['force_2fa'] ) && in_array( 'yes', $options['force_2fa'] ) ) || 'yes' == $active ) {

				/* Let's make sure the user has generated a secret */
				if( '' != $secret ) {

					if( !isset( $totp ) || '' == $totp )
						return new WP_Error( 'no_totp', __( 'Please provide your one time password.', 'wpga' ) );

					if( $this->checkTOTP( $secret, $totp ) ) {

						$used = get_option( 'wpga_used_totp', array() );

						if( is_array( $used ) && !in_array( md5( $totp ), $used ) ) {

							array_push( $used, md5( $totp ) );

							update_option( 'wpga_used_totp', $used );

							return $user;

						} else {

							return new WP_Error( 'expired_totp', __( 'The one time password you used has already been revoked.', 'wpga' ) );

						}

					} else {

						return new WP_Error( 'totp_invalid', __( 'The Google Authenticator one time password is incorrect or expired. Please try with a newly generated password.', 'wpga' ) );

					}

				} else {

					$options 		= get_option( 'wpga_options', array() );
					$attempts 		= (int)get_user_meta( $user->ID, 'wpga_attempts', true );
					$max_attempts 	= ( isset( $options['max_attempts'] ) && '' != $options['max_attempts'] ) ? $options['max_attempts'] : $this->def_attempt;

					if( $max_attempts != 0 ) {

						if( $attempts <= $max_attempts ) {

							// $new = ( '' == $attempts ) ? 0 : intval( $attempts );
							update_user_meta( $user->ID, 'wpga_attempts', $attempts+1, $attempts );
							return $user;

						} else {

							return new WP_Error( '2fa_max_attempts', __( 'You have reached the maximum number of logins WITHOUT using 2-factor authentication. Please contact the admin to reset your account.', 'wpga' ) );

						}

					}

					/* If admin gives unlimited attempts, let's just move on */
					else {

						return $user;

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
	 * Get QR Code
	 *
	 * Generate QR code through Google Chart using https
	 * 
	 * @return (string) QR Code URL
	 */
	public function getQRCodeGoogleUrl() {

		$name 	= $this->settings->getOption( 'blog_name' );
		$secret = esc_attr( get_the_author_meta( 'wpga_secret', get_current_user_id() ) );

		$urlencoded = urlencode('otpauth://totp/' . $name . '?secret=' . $secret );
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

		</table>
	<?php }

	/**
	 * Add admin control fields in user profile
	 */
	public function UserAdminCustomProfileFields() {

		if( !current_user_can( 'administrator' ) || !isset( $_GET['user_id'] ) )
			return;

		$options 		= get_option( 'wpga_options', array() );
		$secret 		= esc_attr( get_the_author_meta( 'wpga_secret', $_GET['user_id'] ) );
		$args 			= array( 'action' => 'revoke', 'user_id' => $_GET['user_id'] );
		$rst_arg		= array( 'action' => 'reset', 'user_id' => $_GET['user_id'] );
		$revoke 		= wp_nonce_url( add_query_arg( $args, admin_url( 'user-edit.php' ) ), 'revoke_key' );
		$rst 			= wp_nonce_url( add_query_arg( $rst_arg, admin_url( 'user-edit.php' ) ), 'reset_key' );
		$attempts 		= (int)get_user_meta( $_GET['user_id'], 'wpga_attempts', true );
		$max_attempts 	= ( isset( $options['max_attempts'] ) && '' != $options['max_attempts'] ) ? (int)$options['max_attempts'] : $this->def_attempt;
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
				<?php _e( 'Google Authenticator', 'wpga' ); ?> <small><a href="#" title="<?php _e( 'If you do not have configured the 2-factor authentication,<br> just leave this field blank and you will be logged-in as usual.', 'wpga' ); ?>" class="wpgahelp">[?]</a></small>
				<br>
				<input id="authenticator" class="input" type="text" size="20" value="" name="totp">
			</label>
		</p>
		<?php
	}

}

$wpga = new WPGA_Admin();