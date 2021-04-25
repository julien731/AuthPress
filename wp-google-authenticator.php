<?php
/**
 * @package   WP Google Authenticator
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2016 Julien Liabeuf
 *
 * @wordpress-plugin
 * Plugin Name:       WP Authenticator
 * Plugin URI:        https://wordpress.org/plugins/wp-google-authenticator/
 * Description:       WP Authenticator provides a safe way to add 2-factor authentication to your WordPress site using the Google 2FA system with the Google Authenticator app.
 * Version:           1.1.1
 * Author:            Julien Liabeuf
 * Author URI:        https://julienliabeuf.com
 * Text Domain:       wpga
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WP_Google_Authenticator' ) ):

	/**
	 * Main WP Google Authenticator class
	 *
	 * This class is the one and only instance of the plugin. It is used
	 * to load the core and all its components.
	 *
	 * @since 1.2.0
	 */
	final class WP_Google_Authenticator {

		/**
		 * @var WP_Google_Authenticator Holds the unique instance of WP Google Authenticator
		 * @since 1.2.0
		 */
		private static $instance;

		/**
		 * Possible error message.
		 *
		 * @since 1.2.0
		 * @var null|WP_Error
		 */
		protected $error = null;

		/**
		 * Minimum version of WordPress required ot run the plugin
		 *
		 * @since 1.2.0
		 * @var string
		 */
		public $wordpress_version_required = '3.8';

		/**
		 * Required version of PHP.
		 *
		 * Follow WordPress latest requirements and require
		 * PHP version 5.2 at least.
		 *
		 * @since 1.2.0
		 * @var string
		 */
		public $php_version_required = '5.2';

		/**
		 * Holds the instance of our authentication class
		 *
		 * @since 1.2.0
		 * @var WPGA_Authenticate
		 */
		public $authenticate;

		/**
		 * Holds the settings class
		 *
		 * @since 1.2
		 * @var WPGA_Settings
		 */
		public $settings;

		/**
		 * Holds the recovery key instance
		 *
		 * @since 1.2
		 * @var WPGA_Recovery_Key
		 */
		public $recovery;

		/**
		 * Holds the access log class instance
		 *
		 * @since 1.2
		 * @var WPGA_Access_Log
		 */
		public $access_log;

		/**
		 * Instantiate and return the unique WP Google Authenticator object
		 *
		 * @since     1.2.0
		 * @return object WP_Google_Authenticator Unique instance of WP Google Authenticator
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Google_Authenticator ) ) {
				self::$instance = new WP_Google_Authenticator;
				self::$instance->init();
			}

			return self::$instance;

		}

		/**
		 * Instantiate the plugin
		 *
		 * @since 1.2.0
		 * @return void
		 */
		private function init() {

			// First of all we need the constants
			self::$instance->setup_constants();

			// Make sure the WordPress version is recent enough
			if ( ! self::$instance->is_version_compatible() ) {
				self::$instance->add_error( sprintf( __( 'WP Google Authenticator requires WordPress version %s or above. Please update WordPress to run this plugin.', 'wpga' ), self::$instance->wordpress_version_required ) );
			}

			// Make sure we have a version of PHP that's not too old
			if ( ! self::$instance->is_php_version_enough() ) {
				self::$instance->add_error( sprintf( __( 'WP Google Authenticator requires PHP version %s or above. Read more information about <a %s>how you can update</a>.', 'wpga' ), self::$instance->wordpress_version_required, 'a href="http://www.wpupdatephp.com/update/" target="_blank"' ) );
			}

			// If we have any error, don't load the plugin
			if ( is_a( self::$instance->error, 'WP_Error' ) ) {
				add_action( 'admin_notices', array( self::$instance, 'display_error' ), 10, 0 );
				return;
			}

			self::$instance->setup_database_constants();
			self::$instance->includes();
			self::$instance->authenticate = new WPGA_Authenticate();
			self::$instance->recovery = new WPGA_Recovery_Key();
			self::$instance->access_log = new WPGA_Access_Log();

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				self::$instance->settings = new WPGA_Settings( 'network' );
			}

			add_action( 'plugins_loaded', array( self::$instance, 'load_plugin_textdomain' ) );

			// Check for network activation
			add_action( 'admin_notices', array( self::$instance, 'multisite_check' ), 5 );

		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.2.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpga' ), '1.2.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.2.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpga' ), '1.2.0' );
		}

		/**
		 * Setup all plugin constants
		 *
		 * @since 1.2.0
		 * @return void
		 */
		private function setup_constants() {
			define( 'WPGA_VERSION', '1.1.1' );
			define( 'WPGA_DB_VERSION', '1' );
			define( 'WPGA_NAME', 'WP Authenticator' );
			define( 'WPGA_AUTHOR', 'Julien Liabeuf' );
			define( 'WPGA_URI', 'https://julienliabeuf.com' );
			define( 'WPGA_URL', plugin_dir_url( __FILE__ ) );
			define( 'WPGA_PATH', plugin_dir_path( __FILE__ ) );
			define( 'WPGA_ROOT', trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
			define( 'WPGA_PREFIX', 'wpga' );
			define( 'WPGA_BASENAME', plugin_basename( __FILE__ ) );
			define( 'WPGA_LOG', false );
		}

		/**
		 * Setup the custom database table constants
		 *
		 * @since 2.0
		 * @return void
		 */
		private function setup_database_constants() {

			global $wpdb;

			define( 'wpga_recovery_keys_table_name', 'wpga_recovery_keys' );
			define( 'wpga_apps_access_log_table_name', 'wpga_apps_access_log' );
			define( 'wpga_recovery_keys_table', $wpdb->prefix . wpga_recovery_keys_table_name );
			define( 'wpga_apps_access_log_table', $wpdb->prefix . wpga_apps_access_log_table_name );

		}

		/**
		 * Check if the core version is compatible with this plugin.
		 *
		 * @since  1.2.0
		 * @return boolean
		 */
		private function is_version_compatible() {

			if ( empty( self::$instance->wordpress_version_required ) ) {
				return true;
			}

			if ( version_compare( get_bloginfo( 'version' ), self::$instance->wordpress_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Check if the version of PHP is compatible with this plugin.
		 *
		 * @since  1.2.0
		 * @return boolean
		 */
		private function is_php_version_enough() {

			/**
			 * No version set, we assume everything is fine.
			 */
			if ( empty( self::$instance->php_version_required ) ) {
				return true;
			}

			if ( version_compare( phpversion(), self::$instance->php_version_required, '<' ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Check if the site is a multisite network and, if so, if the plugin is network activated or not
		 *
		 * If the plugin is not network activated, we display a warning message highlighting the security issue related to having it active on a per-site basis.
		 *
		 * @since 1.2
		 * @return void
		 */
		public function multisite_check() {
			if ( true === is_multisite() && false === self::$instance->is_network_enabled() ) {
				wpga_register_notice( 'wpga_not_network_activated', 'error', sprintf( __( '%2$s is only active on the current site of your network. This introduces a security risk. <strong>It is strongly advised that you network-activate the plugin for maximum security</strong>. <a href="%1$s" target="_blank">Read more about this</a>.', 'wpga' ), '#', WPGA_NAME ) );
			}
		}

		/**
		 * Check if the plugin is network-enabled
		 *
		 * @since 1.2
		 * @return bool
		 */
		public function is_network_enabled() {

			if ( false === is_multisite() ) {
				return false;
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			return is_plugin_active_for_network( WPGA_BASENAME );

		}

		/**
		 * Add error.
		 *
		 * Add a new error to the WP_Error object
		 * and create the object if it doesn't exist yet.
		 *
		 * @since  1.2.0
		 *
		 * @param string $message Error message to add
		 *
		 * @return void
		 */
		private function add_error( $message ) {

			if ( ! is_object( $this->error ) || ! is_a( $this->error, 'WP_Error' ) ) {
				$this->error = new WP_Error();
			}

			$this->error->add( 'addon_error', $message );

		}

		/**
		 * Display error.
		 *
		 * Get all the error messages and display them
		 * in the admin notices.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		public function display_error() {

			if ( ! is_a( $this->error, 'WP_Error' ) ) {
				return;
			}

			$message = self::$instance->error->get_error_messages(); ?>

			<div class="error">
				<p>
					<?php
					if ( count( $message ) > 1 ) {
						echo '<ul>';
						foreach ( $message as $msg ) {
							echo "<li>$msg</li>";
						}
						echo '</li>';
					} else {
						echo $message[0];
					}
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Include all files used sitewide
		 *
		 * @since 1.2.0
		 * @return void
		 */
		private function includes() {

			if ( is_admin() ) {

				// We don't need all this during Ajax processing
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {

					require( WPGA_PATH . 'includes/admin/functions-user-profile.php' );
					require( WPGA_PATH . 'includes/admin/functions-secret.php' );
					require( WPGA_PATH . 'includes/admin/functions-misc.php' );
					require( WPGA_PATH . 'includes/admin/install.php' );

					// Load all the options callbacks
					foreach ( glob( WPGA_PATH . 'includes/admin/views/options/option-*.php' ) as $filename ) {
						include $filename;
					}

				}

			}

			require( WPGA_PATH . 'includes/admin/class-settings.php' );
			require( WPGA_PATH . 'includes/admin/functions-settings.php' );
			require( WPGA_PATH . 'includes/functions-login.php' );
			require( WPGA_PATH . 'includes/functions-totp.php' );
			require( WPGA_PATH . 'includes/functions-users.php' );
			require( WPGA_PATH . 'includes/functions-recovery.php' );
			require( WPGA_PATH . 'includes/functions-deprecated.php' );
			require( WPGA_PATH . 'includes/class-recovery-key.php' );
			require( WPGA_PATH . 'includes/class-authenticate.php' );
			require( WPGA_PATH . 'includes/class-user.php' );
			require( WPGA_PATH . 'includes/class-access-log.php' );
			require( WPGA_PATH . 'includes/functions-apps-passwords.php' );
			require( WPGA_PATH . 'includes/scripts-styles.php' );

		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return boolean True if the language file was loaded, false otherwise
		 * @since    1.2.0
		 */
		public function load_plugin_textdomain() {

			$lang_dir  = WPGA_ROOT . 'languages/';
			$land_path = WPGA_PATH . 'languages/';
			$locale    = apply_filters( 'plugin_locale', get_locale(), 'wpga' );
			$mofile    = "wpga-$locale.mo";

			if ( file_exists( $land_path . $mofile ) ) {
				$language = load_textdomain( 'wpga', $land_path . $mofile );
			} else {
				$language = load_plugin_textdomain( 'wpga', false, $lang_dir );
			}

			return $language;

		}

	}

endif;

/**
 * The main function responsible for returning the unique WP Google Authenticator instance
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 1.2.0
 * @return WP_Google_Authenticator
 */
function WPGA() {
	return WP_Google_Authenticator::instance();
}

// Get WP Google Authenticator Running
WPGA();
