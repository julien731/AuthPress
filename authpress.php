<?php
/**
 * @package   AuthPress
 * @author    Julien Liabeuf <julien@liabeuf.fr>
 * @license   GPL-2.0+
 * @link      https://julienliabeuf.com
 * @copyright 2017 Julien Liabeuf
 *
 * @wordpress-plugin
 * Plugin Name:       AuthPress
 * Plugin URI:        https://wordpress.org/plugins/wp-google-authenticator/
 * Description:       AuthPress protects your WordPress site by adding 2-factor authentication to prevent unauthorized access to your admin.
 * Version:           2.0.0
 * Author:            Julien Liabeuf
 * Author URI:        https://julienliabeuf.com
 * Text Domain:       authpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'AuthPress' ) ) :

	/**
	 * Main AuthPress class
	 *
	 * This class is the one and only instance of the plugin. It is used
	 * to load the core and all its components.
	 *
	 * @since 2.0.0
	 */
	final class AuthPress {

		/**
		 * Holds the unique instance of the plugin.
		 *
		 * @var AuthPress Holds the unique instance of AuthPress.
		 * @since 2.0.0
		 */
		private static $instance;

		/**
		 * Possible error message.
		 *
		 * If something goes wrong, or some requirements aren't met during plugin initialization, the error(s) are stored in the $error variable.
		 *
		 * @since 2.0.0
		 * @var null|WP_Error
		 */
		protected $error = null;

		/**
		 * Minimum version of WordPress required ot run the plugin.
		 *
		 * @since 2.0.0
		 * @var string
		 */
		public $wordpress_version_required = '4.6';

		/**
		 * Required version of PHP.
		 *
		 * PHP version 5.6 might seem high to some, but this plugin aims at increasing the security of a WordPress site. Using an outdated version of PHP is insecure.
		 *
		 * PHP 5.6 is the oldest version that still gets security support (until 31 Dec. 2018). Once 5.6 stops getting security support,
		 * the minimum required version for AuthPress will be bumped to the oldest version of PHP getting security support (most likely 7.0).
		 *
		 * @since 2.0.0
		 * @var string
		 */
		public $php_version_required = '5.6';

		/**
		 * Instantiate and return the unique AuthPress object.
		 *
		 * @since  2.0.0
		 * @return AuthPress
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof AuthPress ) ) {
				self::$instance = new AuthPress;
				self::$instance->init();
			}
			return self::$instance;
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'authpress' ), '2.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpga' ), '2.0.0' );
		}

		/**
		 * Add error.
		 *
		 * Add a new error to the WP_Error object
		 * and create the object if it doesn't exist yet.
		 *
		 * @since  2.0.0
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message to add.
		 *
		 * @return void
		 */
		private function add_error( $code = 'init_error', $message ) {
			if ( ! is_object( self::$instance->error ) || ! is_a( self::$instance->error, 'WP_Error' ) ) {
				self::$instance->error = new WP_Error();
			}
			self::$instance->error->add( $code, $message );
		}

		/**
		 * Get potential error message(s).
		 *
		 * @since 2.0.0
		 * @return null|WP_Error
		 */
		public function get_errors() {
			return self::$instance->error;
		}

		/**
		 * Initialize the plugin.
		 *
		 * This method is what loads everything we need. It is responsible for triggering all the functions that make this plugin work.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		private function init() {

			// First of all, we need to declare our constants.
			self::$instance->setup_constants();

			// Check all requirements and abort plugin initialization if they are not met.
			if ( false === self::$instance->can_init() ) {

				// If we have any error, don't load the plugin
				if ( is_wp_error( $this->get_errors() ) ) {
					add_action( 'admin_notices', array( self::$instance, 'display_error' ), 10, 0 );

					return;
				}

				return;
			}

			// Include all our required files.
			self::$instance->includes();

			// Check for network activation.
			// If the install is WPMS but the plugin is not network-activated, we need to warn the user through an admin notice.
			add_action( 'admin_notices', array( self::$instance, 'multisite_check' ), 5 );
		}

		/**
		 * Declare plugin constants.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		private function setup_constants() {
			define( 'AUTHPRESS_DB_VERSION', 2 );
			define( 'AUTHPRESS_BASENAME',   plugin_basename( __FILE__ ) );
			define( 'AUTHPRESS_PATH',       trailingslashit( plugin_dir_path( __FILE__ ) ) );
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * 1. Check for the language pack in the WordPress core directory
		 * 2. Check for the translation file in the plugin's language directory
		 * 3. Fallback to loading the textdomain the classic way
		 *
		 * @since 2.0.0
		 * @return boolean True if the language file was loaded, false otherwise
		 */
		public function load_plugin_textdomain() {
			$lang_dir       = AUTHPRESS_PATH . 'languages/';
			$lang_path      = AUTHPRESS_PATH . 'languages/';
			$locale         = apply_filters( 'plugin_locale', get_locale(), 'authpress' );
			$mofile         = "authpress-$locale.mo";
			$glotpress_file = WP_LANG_DIR . '/plugins/authpress/' . $mofile;

			// Look for the GlotPress language pack first of all
			if ( file_exists( $glotpress_file ) ) {
				$language = load_textdomain( 'authpress', $glotpress_file );
			} elseif ( file_exists( $lang_path . $mofile ) ) {
				$language = load_textdomain( 'authpress', $lang_path . $mofile );
			} else {
				$language = load_plugin_textdomain( 'authpress', false, $lang_dir );
			}

			return $language;
		}

		/**
		 * Check if the installed PHP version is compatible with the plugin.
		 *
		 * @since 2.0.0
		 * @return bool
		 */
		public function is_php_version_ok() {
			if ( version_compare( phpversion(), self::$instance->php_version_required, '<' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the installed WordPress version is compatible with the plugin.
		 *
		 * @since 2.0.0
		 * @return bool
		 */
		public function is_wordpress_version_ok() {
			if ( version_compare( get_bloginfo( 'version' ), self::$instance->wordpress_version_required, '<' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the plugin can be initialized.
		 *
		 * This method runs a number of checks to make sure that all compatibility requirements are met.
		 * If just one requirement isn't, then the plugin won't be allowed to boot.
		 *
		 * @return bool True if the plugin can initialize, false otherwise.
		 */
		public function can_init() {

			// Make sure we have a version of PHP that's not too old.
			if ( ! self::$instance->is_php_version_ok() ) {
				self::$instance->add_error( 'php_version_too_old', sprintf( __( 'AuthPress requires PHP version %s or above. Read more about <a %s>how you can update on this page</a>.', 'authpress' ), self::$instance->php_version_required, 'a href="http://www.wpupdatephp.com/update/" target="_blank"' ) );
			}

			// Make sure we have a version of WordPress that's not too old.
			if ( ! self::$instance->is_wordpress_version_ok() ) {
				self::$instance->add_error( 'wordpress_version_too_old', sprintf( __( 'AuthPress requires WordPress version %1$s or above. Please update WordPress to run this plugin.', 'authpress' ), self::$instance->wordpress_version_required ) );
			}

			if ( is_wp_error( self::$instance->get_errors() ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check if the site is a multisite network and, if so, if the plugin is network-activated or not.
		 *
		 * If the plugin is not network activated, we display a warning message highlighting the security issue related to having it active on a per-site basis.
		 *
		 * @since 2.0
		 * @return void
		 */
		public function multisite_check() {
			if ( true === is_multisite() && false === self::$instance->is_network_enabled() ) {
				authpress_register_notice( 'authpress_not_network_activated', 'error', sprintf( __( 'AuthPress is only active on the current site of your network. This introduces a security risk. <strong>It is strongly advised that you network-activate the plugin for maximum security</strong>. <a href="%1$s" target="_blank">Read more about this</a>.', 'authpress' ), esc_url( 'https://github.com/julien731/AuthPress/wiki/Multisite-Activation' ) ) );
			}
		}

		/**
		 * Check if the plugin is network-enabled
		 *
		 * @since 2.0
		 * @return bool
		 */
		public function is_network_enabled() {

			if ( false === is_multisite() ) {
				return false;
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}

			return is_plugin_active_for_network( AUTHPRESS_BASENAME );

		}

		/**
		 * Display error.
		 *
		 * Get all the error messages and display them
		 * in the admin notices.
		 *
		 * @since  2.0.0
		 * @return void
		 */
		public function display_error() {

			// Make sure we have WP errors to display.
			if ( ! is_wp_error( $this->get_errors() ) ) {
				return;
			}

			$message = $this->get_errors()->get_error_messages();

			if ( count( $message ) > 1 ) {
				$error = '<ul>';
				foreach ( $message as $msg ) {
					$error .= "<li>$msg</li>";
				}
				$error .= '</ul>';
			} else {
				$error = $message[0];
			}

			printf( '<div class="error"><p>%1$s</p></div>', $error );
		}

		/**
		 * Load all the files required for the plugin to run properly.
		 *
		 * This method checks if the current screen is admin or not and calls the appropriate method.
		 * The files includes are separated in two other methods so that they can be tested. PHPUnit doesn't run as an admin screen so
		 * we need to be able to manually load admin files.
		 *
		 * @since 2.0
		 * @return void
		 */
		protected function includes() {

			// Load required dependencies.
			if ( is_dir( __DIR__ . '/vendor' ) ) {
				require( 'vendor/autoload.php' );
			}
			
			// Load the files that are only necessary on the admin side of WordPress.
			if ( is_admin() ) {
				self::$instance->includes_admin();
			}
		}

		/**
		 * Load all the admin-side files required for the plugin to run.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function includes_admin() {
			// Load the files that aren't required during Ajax processes.
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				require( 'includes/admin/functions-helper.php' );
			}
		}
	}

endif;

/**
 * The main function responsible for returning the unique AuthPress instance.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @since 2.0.0
 * @return AuthPress
 */
function authpress() {
	return AuthPress::instance();
}

/**
 * Initialize the plugin.
 *
 * Such a great responsibility for such a small function... Initializing the entire plugin. Wow, that's deep man.
 */
authpress();
